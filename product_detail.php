<?php
session_start();
require_once 'config.php';
require_once 'includes/flash.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: product.php"); exit; }

$stmt = $conn->prepare(
    "SELECT p.ID, p.Name, p.Category, p.SubType, p.Price, p.Stock, p.Details, p.Ratings, pp.Photo
     FROM products p LEFT JOIN product_photos pp ON p.ID = pp.ProductID
     WHERE p.ID = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    set_flash('error', 'Product not found.');
    header("Location: product.php");
    exit;
}

// Related products (same category, different ID)
$rel_stmt = $conn->prepare(
    "SELECT p.ID, p.Name, p.Price, p.Stock, pp.Photo
     FROM products p LEFT JOIN product_photos pp ON p.ID = pp.ProductID
     WHERE p.Category = ? AND p.ID != ? LIMIT 4"
);
$rel_stmt->bind_param("si", $product['Category'], $id);
$rel_stmt->execute();
$related = $rel_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$rel_stmt->close();

$loggedIn = isset($_SESSION['customer_id']);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['Name']) ?> — EcoGrow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50 min-h-screen flex flex-col">
<?php require_once 'includes/navbar.php'; ?>

<div class="max-w-6xl mx-auto px-4 py-8 flex-1 w-full">
    <?php render_flash(); ?>

    <!-- Breadcrumb -->
    <nav class="text-sm text-gray-400 mb-6">
        <a href="index.php" class="hover:text-green-600 transition">Home</a>
        <span class="mx-2">/</span>
        <a href="product.php" class="hover:text-green-600 transition">Products</a>
        <span class="mx-2">/</span>
        <span class="text-gray-600"><?= htmlspecialchars($product['Name']) ?></span>
    </nav>

    <!-- Product Detail -->
    <div class="bg-white rounded-2xl shadow-sm border border-green-100 overflow-hidden mb-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-0">
            <!-- Image -->
            <div class="bg-green-50 flex items-center justify-center min-h-[320px] lg:min-h-[420px] p-8">
                <?php if (!empty($product['Photo'])): ?>
                    <img src="<?= htmlspecialchars($product['Photo']) ?>"
                         alt="<?= htmlspecialchars($product['Name']) ?>"
                         class="max-h-[380px] max-w-full object-contain rounded-xl shadow">
                <?php else: ?>
                    <div class="text-9xl">🌿</div>
                <?php endif; ?>
            </div>

            <!-- Info -->
            <div class="p-8 flex flex-col">
                <!-- Badges -->
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-xs bg-green-100 text-green-700 px-2.5 py-1 rounded-full font-medium">
                        <?= htmlspecialchars($product['Category']) ?>
                    </span>
                    <span class="text-xs bg-gray-100 text-gray-500 px-2.5 py-1 rounded-full font-medium">
                        <?= htmlspecialchars($product['SubType']) ?>
                    </span>
                    <?php if ($product['Ratings']): ?>
                        <span class="text-xs bg-yellow-100 text-yellow-700 px-2.5 py-1 rounded-full font-medium ml-auto">
                            ⭐ <?= number_format($product['Ratings'], 1) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <h1 class="text-2xl font-bold text-green-800 mb-2"><?= htmlspecialchars($product['Name']) ?></h1>

                <div class="text-3xl font-bold text-green-600 mb-4">$<?= number_format($product['Price'], 2) ?></div>

                <!-- Stock -->
                <div class="mb-5">
                    <?php if ($product['Stock'] <= 0): ?>
                        <span class="inline-flex items-center gap-1 text-sm text-red-600 font-medium bg-red-50 px-3 py-1.5 rounded-lg">
                            ✗ Out of Stock
                        </span>
                    <?php elseif ($product['Stock'] <= 5): ?>
                        <span class="inline-flex items-center gap-1 text-sm text-amber-600 font-medium bg-amber-50 px-3 py-1.5 rounded-lg">
                            ⚡ Only <?= (int)$product['Stock'] ?> left in stock
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1 text-sm text-green-600 font-medium bg-green-50 px-3 py-1.5 rounded-lg">
                            ✓ In Stock (<?= (int)$product['Stock'] ?> available)
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Description -->
                <?php if (!empty($product['Details'])): ?>
                    <p class="text-gray-600 text-sm leading-relaxed mb-6 flex-1">
                        <?= nl2br(htmlspecialchars($product['Details'])) ?>
                    </p>
                <?php endif; ?>

                <!-- Actions -->
                <div class="flex gap-3 flex-wrap">
                    <?php if ($loggedIn && $product['Stock'] > 0): ?>
                        <form method="POST" action="add_to_cart.php">
                            <input type="hidden" name="product_id" value="<?= (int)$product['ID'] ?>">
                            <button type="submit"
                                class="bg-white border-2 border-green-600 text-green-700 font-semibold px-6 py-2.5 rounded-xl hover:bg-green-50 transition">
                                🛒 Add to Cart
                            </button>
                        </form>
                        <a href="order.php?productid=<?= (int)$product['ID'] ?>"
                            class="bg-green-600 text-white font-semibold px-6 py-2.5 rounded-xl hover:bg-green-700 transition">
                            Buy Now
                        </a>
                    <?php elseif (!$loggedIn): ?>
                        <a href="login.php"
                            class="bg-green-600 text-white font-semibold px-6 py-2.5 rounded-xl hover:bg-green-700 transition">
                            Login to Purchase
                        </a>
                    <?php else: ?>
                        <span class="bg-gray-100 text-gray-400 font-semibold px-6 py-2.5 rounded-xl">
                            Out of Stock
                        </span>
                    <?php endif; ?>
                    <a href="product.php"
                        class="text-gray-400 text-sm hover:text-gray-600 transition py-2.5 flex items-center">
                        ← All Products
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if ($related): ?>
    <div>
        <h2 class="text-xl font-bold text-green-800 mb-5">More <?= htmlspecialchars($product['Category']) ?></h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <?php foreach ($related as $r): ?>
            <a href="product_detail.php?id=<?= (int)$r['ID'] ?>"
               class="bg-white rounded-xl border border-green-100 shadow-sm overflow-hidden hover:shadow-md hover:-translate-y-0.5 transition-all block">
                <?php if (!empty($r['Photo'])): ?>
                    <img src="<?= htmlspecialchars($r['Photo']) ?>"
                         alt="<?= htmlspecialchars($r['Name']) ?>"
                         class="w-full h-36 object-cover">
                <?php else: ?>
                    <div class="w-full h-36 bg-green-50 flex items-center justify-center text-5xl">🌿</div>
                <?php endif; ?>
                <div class="p-3">
                    <h3 class="font-semibold text-green-800 text-xs leading-snug mb-1"><?= htmlspecialchars($r['Name']) ?></h3>
                    <p class="text-green-600 font-bold text-sm">$<?= number_format($r['Price'], 2) ?></p>
                    <?php if ($r['Stock'] <= 0): ?>
                        <p class="text-xs text-red-400 mt-1">Out of stock</p>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<footer class="bg-green-800 text-green-200 text-center py-6 text-sm mt-auto">
    <p class="mb-2">🌿 EcoGrow Nursery &copy; <?= date('Y') ?> — Growing Together</p>
    <div class="flex justify-center gap-6 text-xs">
        <a href="index.php" class="hover:text-white transition">Home</a>
        <a href="product.php" class="hover:text-white transition">Products</a>
        <a href="events.php" class="hover:text-white transition">Events</a>
        <a href="about.php" class="hover:text-white transition">About</a>
    </div>
</footer>
</body>
</html>
