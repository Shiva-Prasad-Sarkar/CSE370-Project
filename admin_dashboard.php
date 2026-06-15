<?php
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once 'config.php';

if (!isset($_SESSION['admin'])) { header("Location: admin_login.php"); exit; }

$admin    = $_SESSION['admin'];
$admin_id = (int)$admin['id'];
$msg      = '';
$msg_type = '';

// Order confirmation
if (isset($_POST['confirm_order'])) {
    $order_id = (int)$_POST['order_id'];
    $loc = '';
    $stmt = $conn->prepare(
        "INSERT INTO Admin_Confirms_Orders (AdminID, OrderID, Location, IsPending)
         VALUES (?, ?, ?, 0)
         ON DUPLICATE KEY UPDATE IsPending = 0"
    );
    $stmt->bind_param("iis", $admin_id, $order_id, $loc);
    $stmt->execute();
    $stmt->close();
    $msg = "Order #$order_id confirmed.";
    $msg_type = 'success';
}

// Add product
if (isset($_POST['add_product'])) {
    $name     = trim($_POST['name'] ?? '');
    $category = $_POST['category'] ?? '';
    $subtype  = $_POST['subtype'] ?? '';
    $price    = (float)($_POST['price'] ?? 0);
    $stock    = (int)($_POST['stock'] ?? 0);
    $details  = trim($_POST['details'] ?? '');
    $photo    = trim($_POST['photo_url'] ?? '');

    $valid_combos = ['Accessories' => ['Soil','Glass','Wooden'], 'Plants' => ['Indoor','Outdoor']];

    if (!isset($valid_combos[$category]) || !in_array($subtype, $valid_combos[$category])) {
        $msg = "Invalid subtype '$subtype' for category '$category'.";
        $msg_type = 'error';
    } else {
        $stmt = $conn->prepare("INSERT INTO Products (Name, Category, SubType, Price, Stock, Details) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdis", $name, $category, $subtype, $price, $stock, $details);
        $stmt->execute();
        if (!empty($photo)) {
            $pid  = (int)$conn->insert_id;
            $stmt2 = $conn->prepare("INSERT INTO product_photos (ProductID, Photo) VALUES (?, ?)");
            $stmt2->bind_param("is", $pid, $photo);
            $stmt2->execute();
            $stmt2->close();
        }
        $stmt->close();
        $msg = "Product '$name' added.";
        $msg_type = 'success';
    }
}

// Create workshop
if (isset($_POST['create_workshop'])) {
    $topic   = trim($_POST['topic'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $date    = $_POST['date'] ?? '';
    $type    = $_POST['type'] ?? '';
    $w_pts   = null;
    $w_price = null;

    if ($type === 'Paid') {
        if (empty(trim($_POST['points'] ?? '')) || empty(trim($_POST['price_workshop'] ?? ''))) {
            $msg = 'Paid workshops require both points and price.';
            $msg_type = 'error';
        } else {
            $w_pts   = (int)$_POST['points'];
            $w_price = (float)$_POST['price_workshop'];
        }
    }

    if ($msg_type !== 'error') {
        $stmt = $conn->prepare(
            "INSERT INTO Workshops (Topic, Subject, Date, Type, CreatedBy, Points, Price)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssiid", $topic, $subject, $date, $type, $admin_id, $w_pts, $w_price);
        $stmt->execute();
        $wid = (int)$conn->insert_id;
        $stmt->close();

        $branches_raw = trim($_POST['branches'] ?? '');
        if ($branches_raw !== '') {
            $branch_ids = array_filter(array_map('intval', explode(',', $branches_raw)));
            $stmt2 = $conn->prepare("INSERT INTO Workshops_Branches (WorkshopID, BranchID) VALUES (?, ?)");
            foreach ($branch_ids as $bid) {
                $stmt2->bind_param("ii", $wid, $bid);
                $stmt2->execute();
            }
            $stmt2->close();
        }
        $msg = "Workshop '$topic' created.";
        $msg_type = 'success';
    }
}

// Fetch data
$pending_orders  = $conn->query(
    "SELECT o.*, c.Name AS CustomerName FROM Orders o
     LEFT JOIN Customers c ON o.CustomerID = c.ID
     WHERE o.ID NOT IN (SELECT OrderID FROM Admin_Confirms_Orders WHERE IsPending = 0)
     ORDER BY o.Date DESC"
);
$branches_list   = $conn->query("SELECT ID, Name FROM Branches ORDER BY Name ASC");

$total_orders    = (int)$conn->query("SELECT COUNT(*) c FROM Orders")->fetch_assoc()['c'];
$pending_count   = (int)$conn->query("SELECT COUNT(*) c FROM Orders WHERE ID NOT IN (SELECT OrderID FROM Admin_Confirms_Orders WHERE IsPending=0)")->fetch_assoc()['c'];
$total_customers = (int)$conn->query("SELECT COUNT(*) c FROM Customers WHERE Type='Registered'")->fetch_assoc()['c'];
$total_products  = (int)$conn->query("SELECT COUNT(*) c FROM Products")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — EcoGrow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex">

<!-- Sidebar -->
<aside class="w-60 bg-green-800 text-white flex-shrink-0 flex-col hidden lg:flex min-h-screen">
    <div class="p-5 border-b border-green-700">
        <a href="index.php" class="text-lg font-bold">🌿 EcoGrow</a>
        <p class="text-green-400 text-xs mt-0.5">Admin Panel</p>
    </div>
    <div class="p-5 border-b border-green-700">
        <?php if (!empty($admin['photo'])): ?>
            <img src="<?= htmlspecialchars($admin['photo']) ?>"
                 alt="" class="w-12 h-12 rounded-full object-cover border-2 border-green-500 mb-2">
        <?php else: ?>
            <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center text-lg font-bold mb-2">
                <?= strtoupper(substr($admin['name'], 0, 1)) ?>
            </div>
        <?php endif; ?>
        <p class="font-semibold text-sm"><?= htmlspecialchars($admin['name']) ?></p>
        <p class="text-green-400 text-xs"><?= htmlspecialchars($admin['position']) ?></p>
    </div>
    <nav class="p-4 flex-1 text-sm space-y-1">
        <a onclick="showSection('orders')" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-green-700 cursor-pointer transition">📦 Pending Orders</a>
        <a onclick="showSection('add-product')" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-green-700 cursor-pointer transition">🆕 Add Product</a>
        <a onclick="showSection('workshop')" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-green-700 cursor-pointer transition">🎓 New Workshop</a>
    </nav>
    <div class="p-4 border-t border-green-700 space-y-1 text-sm">
        <a href="index.php" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-green-700 text-green-300 transition">← View Site</a>
        <a href="logout.php" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-red-700 text-red-300 transition">🚪 Logout</a>
    </div>
</aside>

<!-- Main -->
<main class="flex-1 overflow-auto">
    <!-- Mobile top bar -->
    <div class="lg:hidden flex items-center justify-between bg-green-800 text-white px-4 py-3 text-sm">
        <span class="font-bold">🌿 EcoGrow Admin</span>
        <div class="flex gap-4">
            <a href="index.php" class="text-green-300">Site</a>
            <a href="logout.php" class="text-red-300">Logout</a>
        </div>
    </div>

    <div class="p-6 max-w-6xl mx-auto">
        <h1 class="text-xl font-bold text-gray-800 mb-6">Dashboard</h1>

        <?php if ($msg): ?>
            <div class="border-l-4 p-4 mb-6 rounded-r-lg text-sm
                <?= $msg_type === 'success' ? 'bg-green-50 border-green-500 text-green-800' : 'bg-red-50 border-red-500 text-red-800' ?>">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <?php foreach ([
                ['📦', 'Total Orders', $total_orders],
                ['⏳', 'Pending', $pending_count],
                ['👥', 'Customers', $total_customers],
                ['🌿', 'Products', $total_products],
            ] as [$icon, $label, $val]): ?>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <div class="text-2xl mb-1"><?= $icon ?></div>
                <div class="text-2xl font-bold text-gray-800"><?= $val ?></div>
                <div class="text-xs text-gray-400 uppercase tracking-wide mt-0.5"><?= $label ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pending Orders section -->
        <div id="section-orders" class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4">📦 Pending Orders</h2>
            <?php if ($pending_orders && $pending_orders->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100 text-left">
                                <th class="pb-3 pr-4 font-medium">#</th>
                                <th class="pb-3 pr-4 font-medium">Customer</th>
                                <th class="pb-3 pr-4 font-medium">Date</th>
                                <th class="pb-3 pr-4 font-medium">Total</th>
                                <th class="pb-3 pr-4 font-medium">Qty</th>
                                <th class="pb-3 pr-4 font-medium">Address</th>
                                <th class="pb-3 font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php while ($row = $pending_orders->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-3 pr-4 text-gray-400 text-xs">#<?= $row['ID'] ?></td>
                                <td class="py-3 pr-4 font-medium text-gray-800">
                                    <?= htmlspecialchars($row['CustomerName'] ?? 'ID:' . $row['CustomerID']) ?>
                                </td>
                                <td class="py-3 pr-4 text-gray-500 text-xs"><?= htmlspecialchars($row['Date']) ?></td>
                                <td class="py-3 pr-4 text-green-600 font-semibold">$<?= number_format($row['Bill'], 2) ?></td>
                                <td class="py-3 pr-4 text-gray-500"><?= (int)$row['Count'] ?></td>
                                <td class="py-3 pr-4 text-gray-400 text-xs max-w-[140px] truncate"><?= htmlspecialchars($row['Address']) ?></td>
                                <td class="py-3">
                                    <form method="POST">
                                        <input type="hidden" name="order_id" value="<?= (int)$row['ID'] ?>">
                                        <button type="submit" name="confirm_order"
                                            class="bg-green-600 text-white text-xs px-3 py-1.5 rounded-lg hover:bg-green-700 transition font-medium">
                                            Confirm
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-10 text-gray-400">
                    <div class="text-4xl mb-3">✅</div>
                    <p>All orders confirmed.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Two-column forms -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Add Product -->
            <div id="section-add-product" class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-800 mb-4">🆕 Add Product</h2>
                <form method="POST" class="space-y-3">
                    <input type="text" name="name" placeholder="Product Name" required
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <div class="grid grid-cols-2 gap-3">
                        <select name="category" required class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                            <option value="">Category</option>
                            <option value="Plants">Plants</option>
                            <option value="Accessories">Accessories</option>
                        </select>
                        <select name="subtype" required class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                            <option value="">SubType</option>
                            <optgroup label="Plants"><option>Indoor</option><option>Outdoor</option></optgroup>
                            <optgroup label="Accessories"><option>Soil</option><option>Glass</option><option>Wooden</option></optgroup>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <input type="number" step="0.01" name="price" placeholder="Price" required
                            class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                        <input type="number" name="stock" placeholder="Stock" required
                            class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    </div>
                    <textarea name="details" rows="2" placeholder="Description"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 resize-none"></textarea>
                    <input type="text" name="photo_url" placeholder="Image URL (optional)"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <button type="submit" name="add_product"
                        class="w-full bg-green-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition">
                        Add Product
                    </button>
                </form>
            </div>

            <!-- Create Workshop -->
            <div id="section-workshop" class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-800 mb-4">🎓 Create Workshop</h2>
                <form method="POST" class="space-y-3">
                    <input type="text" name="topic" placeholder="Topic" required
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <input type="text" name="subject" placeholder="Subject"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <div class="grid grid-cols-2 gap-3">
                        <input type="date" name="date" required
                            class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                        <select name="type" required class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                            <option value="">Type</option>
                            <option value="Free">Free</option>
                            <option value="Paid">Paid</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <input type="number" name="points" placeholder="Points (Paid)"
                            class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                        <input type="number" step="0.01" name="price_workshop" placeholder="Price (Paid)"
                            class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    </div>
                    <div class="text-xs text-gray-400 -mt-1 mb-1">
                        Available branches:
                        <?php if ($branches_list && $branches_list->num_rows > 0): ?>
                            <?php while ($b = $branches_list->fetch_assoc()): ?>
                                <span class="bg-gray-100 rounded px-1 mr-1"><?= $b['ID'] ?>:<?= htmlspecialchars($b['Name']) ?></span>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                    <input type="text" name="branches" placeholder="Branch IDs e.g. 1,2"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <button type="submit" name="create_workshop"
                        class="w-full bg-green-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition">
                        Create Workshop
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
function showSection(id) {
    document.getElementById('section-' + id).scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>

<?php $conn->close(); ?>
</body>
</html>
