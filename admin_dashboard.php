<?php
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli("localhost", "root", "", "ecogrow");

if (!$conn) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit;
}

$admin = $_SESSION['admin'];
$admin_id = $admin['id'];
$confirm_msg = "";
$workshop_msg = "";

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.html");
    exit;
}

// Handle order confirmation  
if (isset($_POST['confirm_order'])) {
    $order_id = $_POST['order_id'];
    // For simplicity, we're using an empty string for Location.
    // You could also use the order's Address from Orders table if needed.
    $location = "";
    $stmt = $conn->prepare("INSERT INTO Admin_Confirms_Orders (AdminID, OrderID, Location, IsPending) VALUES (?, ?, ?, 0) ON DUPLICATE KEY UPDATE IsPending = 0");
    $stmt->bind_param("iis", $admin_id, $order_id, $location);
    $stmt->execute();
    $stmt->close();
    $confirm_msg = "✅ Order #$order_id confirmed.";
}

// Handle product addition
if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $subtype = $_POST['subtype'];
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $details = $_POST['details'];
    $photo_url = isset($_POST['photo_url']) ? $_POST['photo_url'] : null;

    // Validate Category & SubType combination
    $valid_combinations = [
        'Accessories' => ['Soil', 'Glass', 'Wooden'],
        'Plants' => ['Indoor', 'Outdoor']
    ];

    if (!isset($valid_combinations[$category]) || !in_array($subtype, $valid_combinations[$category])) {
        $confirm_msg = "❌ Invalid SubType '$subtype' for Category '$category'.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO Products (Name, Category, SubType, Price, Stock, Details) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssdis", $name, $category, $subtype, $price, $stock, $details);
            $stmt->execute();
            $stmt->close();

            // Insert image URL if provided.
            if (!empty($photo_url)) {
                $product_id = $conn->insert_id;
                $stmt = $conn->prepare("INSERT INTO product_photos (ProductID, Photo) VALUES (?, ?)");
                $stmt->bind_param("is", $product_id, $photo_url);
                $stmt->execute();
                $stmt->close();
            }
            $confirm_msg = "✅ Product '$name' added to stock.";
        } catch (mysqli_sql_exception $e) {
            $confirm_msg = "❌ Failed to add product: " . $e->getMessage();
        }
    }
}

// Handle workshop creation
if (isset($_POST['create_workshop'])) {
    $w_topic = $_POST['topic'];
    $w_subject = $_POST['subject'];
    $w_date = $_POST['date'];
    $w_type = $_POST['type'];

    // Define Points and Price based on workshop type.
    if ($w_type === 'Paid') {
        if (empty(trim($_POST['points'])) || empty(trim($_POST['price_workshop']))) {
            $workshop_msg = "❌ For Paid workshops, please provide both Points and Price.";
        } else {
            $w_points = intval($_POST['points']);
            $w_price  = floatval($_POST['price_workshop']);
        }
    } else {
        // For Free workshops, Points and Price must be NULL.
        $w_points = null;
        $w_price  = null;
    }

    // Get branch IDs (comma separated) for linking to workshop locations.
    $w_branches = $_POST['branches'];

    if (empty($workshop_msg)) {
        try {
            // Insert the workshop into the Workshops table.
            $stmt = $conn->prepare("INSERT INTO Workshops (Topic, Subject, Date, Type, CreatedBy, Points, Price) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiid", $w_topic, $w_subject, $w_date, $w_type, $admin_id, $w_points, $w_price);
            $stmt->execute();
            $workshop_id = $conn->insert_id;
            $stmt->close();

            // If branch IDs were provided, insert them into Workshops_Branches.
            if (!empty($w_branches)) {
                $branch_ids = explode(",", $w_branches);
                $stmt2 = $conn->prepare("INSERT INTO Workshops_Branches (WorkshopID, BranchID) VALUES (?, ?)");
                foreach ($branch_ids as $branch) {
                    $branch = trim($branch);
                    if (!empty($branch)) {
                        $branch_id = intval($branch);
                        $stmt2->bind_param("ii", $workshop_id, $branch_id);
                        $stmt2->execute();
                    }
                }
                $stmt2->close();
            }
            $workshop_msg = "✅ Workshop '$w_topic' created successfully.";
        } catch (mysqli_sql_exception $e) {
            $workshop_msg = "❌ Failed to create workshop: " . $e->getMessage();
        }
    }
}

// Fetch pending orders – those orders that are not confirmed (IsPending = 0) for this admin.
$orders = $conn->query("SELECT * FROM Orders WHERE ID NOT IN (SELECT OrderID FROM Admin_Confirms_Orders WHERE AdminID = $admin_id AND IsPending = 0)");
?>

<!DOCTYPE html>
<html>
<head>
  <title>Admin Dashboard</title>
  <style>
    body { font-family: Arial; background: #f0f9f5; padding: 20px; }
    .profile, .section { background: #fff; padding: 20px; margin-bottom: 20px; border-radius: 10px; }
    img { max-width: 100px; border-radius: 50%; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    table, th, td { border: 1px solid #ccc; }
    th, td { padding: 10px; text-align: left; }
    form input, form select, form textarea { width: 100%; padding: 8px; margin-bottom: 10px; }
    button { padding: 8px 12px; background: #4caf50; color: white; border: none; cursor: pointer; }
    .logout { float: right; background: #f44336; }
    .message { background:#d4edda; padding:10px; border-radius:8px; margin-bottom: 20px; }
  </style>
</head>
<body>

<!-- Logout Button -->
<form method="post">
  <button class="logout" type="submit" name="logout">🚪 Logout</button>
</form>

<!-- Admin Profile Display -->
<div class="profile">
  <h2>👤 Admin Details</h2>
  <img src="<?= htmlspecialchars($admin['photo']) ?>" alt="Admin Photo">
  <p><strong>Name:</strong> <?= htmlspecialchars($admin['name']) ?></p>
  <p><strong>Position:</strong> <?= htmlspecialchars($admin['position']) ?></p>
  <p><strong>Email:</strong> <?= htmlspecialchars($admin['email']) ?></p>
  <p><strong>ID:</strong> <?= htmlspecialchars($admin['id']) ?></p>
</div>

<!-- Global Message (for products and orders) -->
<?php if ($confirm_msg): ?>
  <div class="message"><?= $confirm_msg ?></div>
<?php endif; ?>

<!-- Add Product Section -->
<div class="section">
  <h3>🆕 Add Product to Stock</h3>
  <form method="post">
    <input type="text" name="name" placeholder="Product Name" required>
    <select name="category" required>
      <option value="">--Category--</option>
      <option value="Accessories">Accessories</option>
      <option value="Plants">Plants</option>
    </select>
    <select name="subtype" required>
      <option value="">--SubType--</option>
      <option value="Soil">Soil</option>
      <option value="Glass">Glass</option>
      <option value="Wooden">Wooden</option>
      <option value="Indoor">Indoor</option>
      <option value="Outdoor">Outdoor</option>
    </select>
    <input type="number" step="0.01" name="price" placeholder="Price" required>
    <input type="number" name="stock" placeholder="Stock" required>
    <textarea name="details" placeholder="Details about the product"></textarea>
    <input type="text" name="photo_url" placeholder="Product Image URL (optional)">
    <button type="submit" name="add_product">Add Product</button>
  </form>
</div>

<!-- Pending Orders Section -->
<div class="section">
  <h3>📦 Pending Orders</h3>
  <table>
    <tr>
      <th>Order ID</th>
      <th>Customer ID</th>
      <th>Date</th>
      <th>Bill</th>
      <th>Quantity</th>
      <th>Address</th>
      <th>Action</th>
    </tr>
    <?php while ($row = $orders->fetch_assoc()): ?>
      <tr>
        <td><?= $row['ID'] ?></td>
        <td><?= $row['CustomerID'] ?></td>
        <td><?= $row['Date'] ?></td>
        <td><?= $row['Bill'] ?></td>
        <td><?= $row['Count'] ?></td>
        <td><?= $row['Address'] ?></td>
        <td>
          <form method="post" style="margin:0;">
            <input type="hidden" name="order_id" value="<?= $row['ID'] ?>">
            <button type="submit" name="confirm_order">Confirm</button>
          </form>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>
</div>

<!-- Create Workshop Section -->
<div class="section">
  <h3>🎓 Create Workshop</h3>
  <?php if ($workshop_msg): ?>
      <div class="message"><?= $workshop_msg ?></div>
  <?php endif; ?>
  <form method="post">
    <input type="text" name="topic" placeholder="Workshop Topic" required>
    <input type="text" name="subject" placeholder="Workshop Subject">
    <input type="date" name="date" required>
    <select name="type" required>
      <option value="">--Type--</option>
      <option value="Free">Free</option>
      <option value="Paid">Paid</option>
    </select>
    <input type="number" name="points" placeholder="Points (for Paid workshops)">
    <input type="number" step="0.01" name="price_workshop" placeholder="Price (for Paid workshops)">
    <input type="text" name="branches" placeholder="Branch IDs (comma separated)">
    <button type="submit" name="create_workshop">Create Workshop</button>
  </form>
</div>

</body>
</html>
