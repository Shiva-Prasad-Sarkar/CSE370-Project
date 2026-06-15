<?php
session_start();
require_once 'config.php';
require_once 'includes/flash.php';

$loggedIn = isset($_SESSION['customer_id']);

// Build safe query with prepared statements
$sql    = "SELECT p.ID, p.Name, p.Category, p.SubType, p.Price, p.Stock, p.Details, pp.Photo
           FROM products p LEFT JOIN product_photos pp ON p.ID = pp.ProductID";
$where  = [];
$params = [];
$types  = '';

if (!empty($_GET['category'])) {
    $where[]  = "p.Category = ?";
    $params[] = $_GET['category'];
    $types   .= 's';
}
if (!empty($_GET['search'])) {
    $where[]  = "p.Name LIKE ?";
    $params[] = '%' . $_GET['search'] . '%';
    $types   .= 's';
}
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$allowed_sorts = [
    'name_asc'   => 'p.Name ASC',
    'name_desc'  => 'p.Name DESC',
    'price_asc'  => 'p.Price ASC',
    'price_desc' => 'p.Price DESC',
];
if (!empty($_GET['sort']) && isset($allowed_sorts[$_GET['sort']])) {
    $sql .= " ORDER BY " . $allowed_sorts[$_GET['sort']];
}
if (!empty($_GET['limit']) && ctype_digit($_GET['limit'])) {
    $sql .= " LIMIT " . (int)$_GET['limit'];
}

if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products — EcoGrow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50 min-h-screen flex flex-col">
<?php require_once 'includes/navbar.php'; ?>

<div class="bg-green-700 text-white py-10 px-4">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold">🌿 Our Products</h1>
        <p class="text-green-200 mt-1 text-sm">Plants, accessories, and everything you need to grow</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 py-8 flex-1 w-full">
    <?php render_flash(); ?>

    <!-- Filter Bar -->
    <form method="GET" class="bg-white rounded-xl shadow-sm border border-green-100 p-4 mb-8">
        <div class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">Category</label>
                <select name="category" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <option value="">All</option>
                    <option value="Plants" <?= ($_GET['category'] ?? '') === 'Plants' ? 'selected' : '' ?>>Plants</option>
                    <option value="Accessories" <?= ($_GET['category'] ?? '') === 'Accessories' ? 'selected' : '' ?>>Accessories</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">Sort</label>
                <select name="sort" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <option value="">Default</option>
                    <option value="name_asc"   <?= ($_GET['sort'] ?? '') === 'name_asc'   ? 'selected' : '' ?>>Name A–Z</option>
                    <option value="name_desc"  <?= ($_GET['sort'] ?? '') === 'name_desc'  ? 'selected' : '' ?>>Name Z–A</option>
                    <option value="price_asc"  <?= ($_GET['sort'] ?? '') === 'price_asc'  ? 'selected' : '' ?>>Price ↑</option>
                    <option value="price_desc" <?= ($_GET['sort'] ?? '') === 'price_desc' ? 'selected' : '' ?>>Price ↓</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">Show</label>
                <select name="limit" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <option value="">All</option>
                    <?php foreach ([5, 10, 20, 30] as $l): ?>
                        <option value="<?= $l ?>" <?= ($_GET['limit'] ?? '') == $l ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">Search</label>
                <input type="text" name="search" placeholder="Search by name..."
                    value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
            </div>
            <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition">
                Apply
            </button>
            <?php if (!empty(array_filter($_GET))): ?>
                <a href="product.php" class="text-gray-400 text-sm hover:text-gray-600 py-2">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
            <div class="bg-white rounded-xl shadow-sm border border-green-100 overflow-hidden hover:shadow-md transition flex flex-col">
                <a href="product_detail.php?id=<?= (int)$row['ID'] ?>">
                    <?php if (!empty($row['Photo'])): ?>
                        <img src="<?= htmlspecialchars($row['Photo']) ?>"
                             alt="<?= htmlspecialchars($row['Name']) ?>"
                             class="w-full h-48 object-cover hover:opacity-90 transition">
                    <?php else: ?>
                        <div class="w-full h-48 bg-green-100 flex items-center justify-center text-5xl">🌿</div>
                    <?php endif; ?>
                </a>

                <div class="p-4 flex flex-col flex-1">
                    <div class="flex items-start justify-between mb-2 gap-2">
                        <a href="product_detail.php?id=<?= (int)$row['ID'] ?>" class="hover:text-green-600 transition">
                            <h3 class="font-semibold text-green-800 text-sm leading-snug"><?= htmlspecialchars($row['Name']) ?></h3>
                        </a>
                        <span class="shrink-0 text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full"><?= htmlspecialchars($row['Category']) ?></span>
                    </div>
                    <p class="text-xl font-bold text-green-600 mb-2">$<?= number_format($row['Price'], 2) ?></p>
                    <p class="text-xs text-gray-400 mb-1">Type: <?= htmlspecialchars($row['SubType']) ?></p>
                    <p class="text-xs text-gray-500 mb-3 flex-1 leading-relaxed">
                        <?= htmlspecialchars(mb_substr($row['Details'], 0, 80)) ?><?= mb_strlen($row['Details']) > 80 ? '…' : '' ?>
                    </p>

                    <?php if ($row['Stock'] <= 0): ?>
                        <span class="text-xs text-red-500 font-semibold mb-2 block">Out of Stock</span>
                    <?php else: ?>
                        <p class="text-xs text-gray-400 mb-3"><?= $row['Stock'] ?> in stock</p>
                    <?php endif; ?>

                    <?php if ($loggedIn && $row['Stock'] > 0): ?>
                        <form method="POST" action="add_to_cart.php">
                            <input type="hidden" name="product_id" value="<?= (int)$row['ID'] ?>">
                            <button type="submit"
                                class="w-full bg-green-600 text-white text-sm py-2 rounded-lg hover:bg-green-700 transition font-medium">
                                Add to Cart
                            </button>
                        </form>
                    <?php elseif (!$loggedIn): ?>
                        <a href="login.php"
                            class="w-full block text-center bg-gray-100 text-gray-500 text-sm py-2 rounded-lg hover:bg-gray-200 transition">
                            Login to Buy
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-span-4 text-center py-20 text-gray-400">
                <div class="text-5xl mb-4">🔍</div>
                <p class="text-lg">No products match your criteria.</p>
                <a href="product.php" class="text-green-600 text-sm mt-2 inline-block hover:underline">Clear filters</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer class="bg-green-800 text-green-200 text-center py-6 text-sm mt-auto">
    🌿 EcoGrow Nursery &copy; <?= date('Y') ?> — Growing Together
</footer>

<?php $conn->close(); ?>
</body>
</html>
