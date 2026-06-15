<?php
session_start();
require_once 'config.php';
require_once 'includes/flash.php';

$branches_result = $conn->query("SELECT ID, Name, Location, Manager, Ratings, Details FROM branches ORDER BY Name ASC");
$branches = $branches_result ? $branches_result->fetch_all(MYSQLI_ASSOC) : [];
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us — EcoGrow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50 min-h-screen flex flex-col">
<?php require_once 'includes/navbar.php'; ?>

<!-- Hero -->
<div class="bg-green-700 text-white py-16 px-4">
    <div class="max-w-4xl mx-auto text-center">
        <h1 class="text-4xl font-bold mb-4">About EcoGrow</h1>
        <p class="text-green-200 text-lg max-w-2xl mx-auto">
            We're passionate about bringing nature closer to you — one plant at a time.
        </p>
    </div>
</div>

<!-- Mission -->
<div class="max-w-5xl mx-auto px-4 py-14">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-16">
        <div>
            <h2 class="text-3xl font-bold text-green-800 mb-4">Our Mission</h2>
            <p class="text-gray-600 text-base leading-relaxed mb-4">
                EcoGrow was founded with a simple goal: make plant care accessible and enjoyable for everyone.
                From seasoned gardeners to first-time plant parents, we offer the plants, tools, and expertise
                to help every green space thrive.
            </p>
            <p class="text-gray-600 text-base leading-relaxed">
                We believe healthy plants create healthier people and healthier communities. Every purchase
                supports sustainable growing practices and helps us plant more trees.
            </p>
        </div>
        <div class="bg-green-100 rounded-2xl p-8 text-center">
            <div class="text-8xl mb-4">🌿</div>
            <p class="text-green-700 font-semibold text-lg">Growing Together Since 2020</p>
        </div>
    </div>

    <!-- Why choose us -->
    <div class="mb-16">
        <h2 class="text-3xl font-bold text-green-800 text-center mb-10">Why Choose EcoGrow?</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            $features = [
                ['🌱', 'Expert Curation', 'Every plant and product in our catalog is hand-selected by our team of horticulture experts.'],
                ['🚚', 'Fast Delivery', 'We carefully package and ship plants so they arrive healthy and ready to grow.'],
                ['🎓', 'Free Workshops', 'Learn from the best at our regular workshops and events — many are completely free.'],
                ['♻️', 'Eco-Friendly', 'Sustainable packaging, responsible sourcing, and a commitment to the environment.'],
                ['💬', 'Expert Support', 'Our team is available to help with any questions about plant care and growing.'],
                ['⭐', 'Quality Guarantee', 'Not satisfied? We stand behind our products with a straightforward returns policy.'],
            ];
            foreach ($features as [$icon, $title, $desc]):
            ?>
            <div class="bg-white rounded-xl border border-green-100 shadow-sm p-6">
                <div class="text-3xl mb-3"><?= $icon ?></div>
                <h3 class="font-semibold text-green-800 text-base mb-2"><?= $title ?></h3>
                <p class="text-gray-500 text-sm leading-relaxed"><?= $desc ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Branches -->
    <?php if ($branches): ?>
    <div class="mb-16">
        <h2 class="text-3xl font-bold text-green-800 text-center mb-4">Our Locations</h2>
        <p class="text-gray-500 text-center mb-10">Visit us at any of our branches across the city.</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($branches as $b): ?>
            <div class="bg-white rounded-xl border border-green-100 shadow-sm p-6">
                <div class="flex items-start justify-between gap-2 mb-3">
                    <h3 class="font-bold text-green-800 text-base"><?= htmlspecialchars($b['Name']) ?></h3>
                    <?php if ($b['Ratings']): ?>
                        <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full font-medium shrink-0">
                            ⭐ <?= number_format($b['Ratings'], 1) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-sm text-gray-500 mb-2">📍 <?= htmlspecialchars($b['Location']) ?></p>
                <?php if ($b['Manager']): ?>
                    <p class="text-sm text-gray-500 mb-2">👤 Manager: <?= htmlspecialchars($b['Manager']) ?></p>
                <?php endif; ?>
                <?php if ($b['Details']): ?>
                    <p class="text-xs text-gray-400 leading-relaxed mt-3"><?= htmlspecialchars($b['Details']) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- CTA -->
    <div class="bg-green-700 rounded-2xl p-10 text-center text-white">
        <h2 class="text-2xl font-bold mb-3">Ready to Start Growing?</h2>
        <p class="text-green-200 mb-6">Browse our curated collection of plants and accessories.</p>
        <div class="flex justify-center gap-4 flex-wrap">
            <a href="product.php" class="bg-white text-green-700 font-semibold px-6 py-2.5 rounded-xl hover:bg-green-50 transition">
                Shop Products
            </a>
            <a href="events.php" class="bg-green-600 text-white font-semibold px-6 py-2.5 rounded-xl border border-green-500 hover:bg-green-500 transition">
                Join a Workshop
            </a>
        </div>
    </div>
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
