<?php
session_start();
require_once 'config.php';
require_once 'includes/flash.php';

if (!isset($_SESSION['customer_id'])) { header("Location: login.php"); exit; }

$user_id = (int)$_SESSION['customer_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity   = (int)($_POST['quantity'] ?? 0);
    $address    = trim($_POST['address'] ?? '');

    if ($quantity <= 0 || empty($address)) {
        set_flash('error', 'Invalid quantity or missing address.');
        $conn->close();
        header("Location: order.php?productid=$product_id");
        exit;
    }

    $stmt = $conn->prepare("SELECT Price, Stock FROM products WHERE ID = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $prod = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$prod) {
        set_flash('error', 'Product not found.');
        $conn->close();
        header("Location: product.php");
        exit;
    }
    if ($quantity > $prod['Stock']) {
        set_flash('error', 'Requested quantity exceeds available stock.');
        $conn->close();
        header("Location: order.php?productid=$product_id");
        exit;
    }

    $bill = $prod['Price'] * $quantity;

    $stmt = $conn->prepare(
        "INSERT INTO orders (CustomerID, Date, Bill, Count, Address, Product_Id)
         VALUES (?, CURDATE(), ?, ?, ?, ?)"
    );
    $stmt->bind_param("idisi", $user_id, $bill, $quantity, $address, $product_id);
    $stmt->execute();
    $new_order_id = (int)$conn->insert_id;
    $stmt->close();

    $stmt = $conn->prepare("UPDATE products SET Stock = Stock - ? WHERE ID = ?");
    $stmt->bind_param("ii", $quantity, $product_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM cart WHERE CustomerID = ? AND ProductID = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $stmt->close();

    // Record initial order status
    $stmt_s = $conn->prepare("INSERT IGNORE INTO order_status (OrderID, Status) VALUES (?, 'Placed')");
    if ($stmt_s) { $stmt_s->bind_param("i", $new_order_id); $stmt_s->execute(); $stmt_s->close(); }

    $conn->close();
    set_flash('success', 'Order placed! We will process it soon.');
    header("Location: user.php");
    exit;
}

// GET — show order form
$product_id = (int)($_GET['productid'] ?? 0);
if (!$product_id) { header("Location: product.php"); exit; }

$stmt = $conn->prepare("SELECT ID, Name, Price, Stock FROM products WHERE ID = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    set_flash('error', 'Product not found.');
    $conn->close();
    header("Location: product.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order — EcoGrow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50 min-h-screen flex flex-col">
<?php require_once 'includes/navbar.php'; ?>

<div class="flex-1 flex items-start justify-center py-12 px-4">
    <div class="w-full max-w-lg">
        <?php render_flash(); ?>

        <div class="bg-white rounded-2xl shadow-sm border border-green-100 p-8">
            <h1 class="text-2xl font-bold text-green-800 mb-6">Place Order</h1>

            <!-- Product summary -->
            <div class="bg-green-50 rounded-xl p-4 mb-6 border border-green-100">
                <h3 class="font-semibold text-green-800 mb-2"><?= htmlspecialchars($product['Name']) ?></h3>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Unit Price</span>
                    <span class="text-green-600 font-semibold">$<?= number_format($product['Price'], 2) ?></span>
                </div>
                <div class="flex justify-between text-sm mt-1">
                    <span class="text-gray-500">Available Stock</span>
                    <span class="text-gray-700"><?= (int)$product['Stock'] ?> units</span>
                </div>
            </div>

            <form method="POST" action="order.php">
                <input type="hidden" name="product_id" value="<?= (int)$product['ID'] ?>">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                    <input type="number" name="quantity" id="qty" value="1"
                           min="1" max="<?= (int)$product['Stock'] ?>" required
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                </div>

                <div class="bg-green-50 rounded-xl p-3 mb-4 flex justify-between items-center">
                    <span class="text-sm text-gray-600">Total</span>
                    <span class="text-xl font-bold text-green-700" id="total">$<?= number_format($product['Price'], 2) ?></span>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Address</label>
                    <textarea name="address" rows="3" required
                        placeholder="Enter your full delivery address..."
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 resize-none"></textarea>
                </div>

                <button type="submit"
                    class="w-full bg-green-600 text-white font-semibold py-3 rounded-xl hover:bg-green-700 transition">
                    Confirm Order
                </button>
            </form>

            <a href="user.php" class="block text-center text-sm text-gray-400 mt-4 hover:text-gray-600">← Back to profile</a>
        </div>
    </div>
</div>

<footer class="bg-green-800 text-green-200 text-center py-6 text-sm">
    🌿 EcoGrow Nursery &copy; <?= date('Y') ?>
</footer>

<script>
const unit = <?= (float)$product['Price'] ?>;
const qty  = document.getElementById('qty');
const tot  = document.getElementById('total');
qty.addEventListener('input', () => {
    tot.textContent = '$' + (unit * (parseInt(qty.value) || 0)).toFixed(2);
});
</script>

<?php $conn->close(); ?>
</body>
</html>
