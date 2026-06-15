<?php
session_start();
require_once 'config.php';
require_once 'includes/flash.php';

$branches = $conn->query("SELECT * FROM Branches ORDER BY ID ASC");
$reviews  = $conn->query(
    "SELECT CR.Comments, C.Name FROM Customers_Reviews CR
     JOIN Customers C ON CR.CustomerID = C.ID
     ORDER BY CR.CustomerID DESC LIMIT 10"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoGrow Nursery — Home</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50 min-h-screen flex flex-col">
<?php require_once 'includes/navbar.php'; ?>

<!-- Hero -->
<section class="relative bg-green-800 text-white py-24 px-4 overflow-hidden">
    <div class="absolute inset-0 bg-cover bg-center opacity-20"
         style="background-image:url('https://swanhose.com/cdn/shop/articles/water-plant-growth.jpg?v=1683652693')"></div>
    <div class="relative max-w-4xl mx-auto text-center">
        <h1 class="text-5xl font-bold mb-4 leading-tight">Welcome to EcoGrow Nursery 🌿</h1>
        <p class="text-xl text-green-200 mb-8">Your one-stop destination for plants, accessories, and gardening expertise.</p>
        <div class="flex flex-wrap gap-4 justify-center">
            <a href="product.php" class="bg-white text-green-800 font-semibold px-6 py-3 rounded-lg hover:bg-green-50 transition shadow">Browse Products</a>
            <a href="events.php" class="border border-white text-white font-semibold px-6 py-3 rounded-lg hover:bg-green-700 transition">Join Workshops</a>
        </div>
    </div>
</section>

<div class="max-w-6xl mx-auto px-4 mt-8">
    <?php render_flash(); ?>
</div>

<!-- Branches -->
<section class="max-w-6xl mx-auto px-4 py-12 w-full">
    <h2 class="text-3xl font-bold text-green-800 mb-1 text-center">Our Branches</h2>
    <p class="text-center text-gray-500 mb-8 text-sm">Find a location near you</p>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if ($branches && $branches->num_rows > 0): ?>
            <?php while ($b = $branches->fetch_assoc()): ?>
            <div class="bg-white rounded-xl shadow-sm border border-green-100 p-6 hover:shadow-md transition">
                <div class="flex items-start gap-3 mb-3">
                    <span class="text-2xl">🏬</span>
                    <h3 class="font-semibold text-lg text-green-800"><?= htmlspecialchars($b['Name']) ?></h3>
                </div>
                <p class="text-gray-600 text-sm mb-1">📍 <?= htmlspecialchars($b['Location']) ?></p>
                <p class="text-gray-600 text-sm mb-1">👤 <?= htmlspecialchars($b['Manager']) ?></p>
                <div class="flex items-center gap-1 mb-3">
                    <span class="text-yellow-400 text-sm">★</span>
                    <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($b['Ratings']) ?></span>
                </div>
                <p class="text-gray-500 text-sm"><?= nl2br(htmlspecialchars($b['Details'])) ?></p>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="col-span-3 text-center text-gray-400 py-10">No branches listed yet.</p>
        <?php endif; ?>
    </div>
</section>

<!-- Reviews -->
<section class="bg-white py-14">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-green-800 mb-1 text-center">Customer Reviews</h2>
        <p class="text-center text-gray-500 mb-8 text-sm">What our community is saying</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-10">
            <?php if ($reviews && $reviews->num_rows > 0): ?>
                <?php while ($r = $reviews->fetch_assoc()): ?>
                <div class="bg-green-50 rounded-xl p-5 border border-green-100">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 bg-green-200 rounded-full flex items-center justify-center text-green-800 font-bold text-sm shrink-0">
                            <?= strtoupper(substr($r['Name'], 0, 1)) ?>
                        </div>
                        <span class="font-semibold text-green-800 text-sm"><?= htmlspecialchars($r['Name']) ?></span>
                    </div>
                    <p class="text-gray-600 text-sm leading-relaxed"><?= nl2br(htmlspecialchars($r['Comments'])) ?></p>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="col-span-2 text-center text-gray-400 py-8">No reviews yet. Be the first!</p>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['customer_id'])): ?>
            <div class="max-w-xl mx-auto">
                <h3 class="text-base font-semibold text-green-800 mb-3">Share Your Experience</h3>
                <form method="POST" action="add_review.php">
                    <textarea name="review" rows="4" required placeholder="Tell us about your experience..."
                        class="w-full border border-green-200 rounded-xl p-4 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 resize-none"></textarea>
                    <button type="submit"
                        class="mt-3 bg-green-600 text-white px-6 py-2.5 rounded-lg hover:bg-green-700 transition font-medium text-sm">
                        Submit Review
                    </button>
                </form>
            </div>
        <?php else: ?>
            <p class="text-center text-gray-500 text-sm">
                <a href="login.php" class="text-green-600 font-medium hover:underline">Sign in</a> to share your experience.
            </p>
        <?php endif; ?>
    </div>
</section>

<footer class="bg-green-800 text-green-200 text-center py-6 text-sm mt-auto">
    🌿 EcoGrow Nursery &copy; <?= date('Y') ?> — Growing Together
</footer>

<?php $conn->close(); ?>
</body>
</html>
