<nav class="bg-green-700 shadow-lg sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center gap-8">
                <a href="index.php" class="text-white text-xl font-bold flex items-center gap-2 shrink-0">
                    🌿 EcoGrow
                </a>
                <div class="hidden md:flex items-center gap-1">
                    <a href="index.php" class="text-green-100 hover:text-white hover:bg-green-600 px-3 py-2 rounded-md text-sm font-medium transition">Home</a>
                    <a href="product.php" class="text-green-100 hover:text-white hover:bg-green-600 px-3 py-2 rounded-md text-sm font-medium transition">Products</a>
                    <a href="events.php" class="text-green-100 hover:text-white hover:bg-green-600 px-3 py-2 rounded-md text-sm font-medium transition">Events</a>
                    <a href="about.php" class="text-green-100 hover:text-white hover:bg-green-600 px-3 py-2 rounded-md text-sm font-medium transition">About</a>
                    <a href="contact.php" class="text-green-100 hover:text-white hover:bg-green-600 px-3 py-2 rounded-md text-sm font-medium transition">Contact</a>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <?php if (isset($_SESSION['admin'])): ?>
                    <a href="admin_dashboard.php" class="text-green-100 hover:text-white hover:bg-green-600 px-3 py-2 rounded-md text-sm font-medium transition">Dashboard</a>
                    <a href="logout.php" class="bg-white text-green-700 hover:bg-green-50 px-4 py-2 rounded-md text-sm font-semibold transition">Logout</a>
                <?php elseif (isset($_SESSION['customer_id'])): ?>
                    <a href="user.php" class="text-green-100 hover:text-white hover:bg-green-600 px-3 py-2 rounded-md text-sm font-medium transition">👤 Profile</a>
                    <a href="logout.php" class="bg-white text-green-700 hover:bg-green-50 px-4 py-2 rounded-md text-sm font-semibold transition">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="text-green-100 hover:text-white hover:bg-green-600 px-3 py-2 rounded-md text-sm font-medium transition">Login</a>
                    <a href="register.php" class="bg-white text-green-700 hover:bg-green-50 px-4 py-2 rounded-md text-sm font-semibold transition">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
