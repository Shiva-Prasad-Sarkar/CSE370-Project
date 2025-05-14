<?php
session_start();

// Redirect to login if user is not logged in.
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.html");
    exit();
}

$conn = new mysqli("localhost", "root", "", "ecogrow");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['customer_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if product id is provided via URL.
    if (!isset($_GET['productid'])) {
        echo "<script>alert('No product selected.'); window.location.href='product.php';</script>";
        exit();
    }
    $product_id = intval($_GET['productid']);
    
    // Fetch product details from the Products table.
    $stmt = $conn->prepare("SELECT ID, Name, Price, Stock FROM Products WHERE ID = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result || $result->num_rows === 0) {
        echo "<script>alert('Product not found.'); window.location.href='product.php';</script>";
        exit();
    }
    $product = $result->fetch_assoc();
    $stmt->close();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>Place Order - EcoGrow</title>
      <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
        .order-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 6px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .order-container h2 { margin-bottom: 20px; }
        .order-details p { margin: 5px 0; }
        form label { display: block; margin-top: 10px; }
        form input[type="number"],
        form textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; margin-top: 5px; }
        form input[type="submit"] {
            margin-top: 20px;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            background-color: #2f5d50;
            color: #fff;
            cursor: pointer;
        }
        form input[type="submit"]:hover { background-color: #4caf50; }
        #totalPrice {
            margin-top: 10px;
            font-weight: bold;
        }
      </style>
    </head>
    <body>
      <div class="order-container">
        <h2>Place Order</h2>
        <div class="order-details">
          <p><strong>Product:</strong> <?php echo htmlspecialchars($product['Name']); ?></p>
          <p><strong>Price:</strong> $<?php echo number_format($product['Price'], 2); ?></p>
          <p><strong>Available Stock:</strong> <?php echo htmlspecialchars($product['Stock']); ?></p>
        </div>
        <form method="post" action="order.php">
          <input type="hidden" name="product_id" value="<?php echo $product['ID']; ?>">
          <label for="quantity">Quantity:</label>
          <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?php echo $product['Stock']; ?>" required>
          
          <p id="totalPrice">Total Price: $<?php echo number_format($product['Price'], 2); ?></p>
          
          <label for="address">Delivery Address:</label>
          <textarea name="address" id="address" rows="3" required></textarea>
          
          <input type="submit" value="Place Order">
        </form>
      </div>
      
      <script>
        // JavaScript to dynamically update the total price
        var unitPrice = <?php echo $product['Price']; ?>;
        var quantityInput = document.getElementById("quantity");
        var totalPriceElement = document.getElementById("totalPrice");

        function updateTotalPrice() {
            var qty = parseInt(quantityInput.value);
            if (!isNaN(qty)) {
                var total = unitPrice * qty;
                totalPriceElement.textContent = "Total Price: $" + total.toFixed(2);
            } else {
                totalPriceElement.textContent = "Total Price: $0.00";
            }
        }
        quantityInput.addEventListener("input", updateTotalPrice);
        updateTotalPrice();  // Initialize on load
      </script>
    </body>
    </html>
    <?php
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process order submission.
    if (!isset($_POST['product_id'], $_POST['quantity'], $_POST['address'])) {
        echo "<script>alert('Incomplete order data.'); window.location.href='product.php';</script>";
        exit();
    }
    
    $product_id = intval($_POST['product_id']);
    $quantity   = intval($_POST['quantity']);
    $address    = trim($_POST['address']);
    
    if ($quantity <= 0 || empty($address)) {
        echo "<script>alert('Invalid quantity or missing address.'); window.location.href='order.php?productid=".$product_id."';</script>";
        exit();
    }
    
    // Re-fetch product details to validate stock and obtain price.
    $stmt = $conn->prepare("SELECT Price, Stock FROM Products WHERE ID = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result || $result->num_rows === 0) {
        echo "<script>alert('Product not found.'); window.location.href='product.php';</script>";
        exit();
    }
    $prod = $result->fetch_assoc();
    $stmt->close();
    
    if ($quantity > $prod['Stock']) {
        echo "<script>alert('Requested quantity exceeds available stock.'); window.location.href='order.php?productid=".$product_id."';</script>";
        exit();
    }
    
    $price = $prod['Price'];
    $bill  = $price * $quantity;
    
    // Insert a new order in the Orders table (including Product_Id).
    $stmt = $conn->prepare("INSERT INTO Orders (CustomerID, Date, Bill, Count, Address, Product_Id) VALUES (?, CURDATE(), ?, ?, ?, ?)");
    if (!$stmt) {
        echo "<script>alert('Order preparation error.'); window.location.href='product.php';</script>";
        exit();
    }
    // Bind parameters: CustomerID (int), Bill (double), Count (int), Address (string), Product_Id (int).
    $stmt->bind_param("idisi", $user_id, $bill, $quantity, $address, $product_id);
    if (!$stmt->execute()) {
        echo "<script>alert('Failed to place order: " . $stmt->error . "'); window.location.href='product.php';</script>";
        exit();
    }
    $stmt->close();
    
    // Update the product stock by decreasing it with the ordered quantity.
    $stmt = $conn->prepare("UPDATE Products SET Stock = Stock - ? WHERE ID = ?");
    $stmt->bind_param("ii", $quantity, $product_id);
    if (!$stmt->execute()) {
        echo "<script>alert('Unable to update stock: " . $stmt->error . "'); window.location.href='product.php';</script>";
        $stmt->close();
        exit();
    }
    $stmt->close();
    
    // After placing the order, delete the corresponding row from the Cart table.
    $stmt = $conn->prepare("DELETE FROM Cart WHERE CustomerID = ? AND ProductID = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $stmt->close();
    
    echo "<script>alert('Order placed successfully!'); window.location.href='user.php';</script>";
    exit();
}

$conn->close();
?>
