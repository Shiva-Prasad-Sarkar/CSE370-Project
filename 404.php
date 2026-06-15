<?php
session_start();
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found — EcoGrow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50 min-h-screen flex flex-col">
<?php require_once 'includes/navbar.php'; ?>

<div class="flex-1 flex items-center justify-center px-4 py-20">
    <div class="text-center">
        <div class="text-8xl mb-6">🌿</div>
        <h1 class="text-6xl font-bold text-green-800 mb-3">404</h1>
        <h2 class="text-xl font-semibold text-gray-700 mb-3">Page Not Found</h2>
        <p class="text-gray-400 mb-8 max-w-sm">The page you're looking for doesn't exist or has been moved.</p>
        <div class="flex justify-center gap-4 flex-wrap">
            <a href="index.php" class="bg-green-600 text-white font-semibold px-6 py-2.5 rounded-xl hover:bg-green-700 transition">
                Go Home
            </a>
            <a href="product.php" class="bg-white text-green-700 border border-green-200 font-semibold px-6 py-2.5 rounded-xl hover:bg-green-50 transition">
                Browse Products
            </a>
        </div>
    </div>
</div>

<footer class="bg-green-800 text-green-200 text-center py-6 text-sm">
    🌿 EcoGrow Nursery &copy; <?= date('Y') ?>
</footer>
</body>
</html>
