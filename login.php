<?php
session_start();
if (isset($_SESSION['customer_id'])) { header("Location: user.php"); exit; }

require_once 'config.php';
require_once 'includes/flash.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (empty($email) || empty($pass)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT ID, Name, Password FROM Customers WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            $verified = password_verify($pass, $user['Password']);
            if (!$verified && $pass === $user['Password']) {
                // Plain-text match — migrate to bcrypt on the fly
                $hashed = password_hash($pass, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE Customers SET Password = ? WHERE ID = ?");
                $upd->bind_param("si", $hashed, $user['ID']);
                $upd->execute();
                $upd->close();
                $verified = true;
            }
            if ($verified) {
                $_SESSION['customer_id'] = $user['ID'];
                set_flash('success', 'Welcome back, ' . $user['Name'] . '!');
                $conn->close();
                header("Location: index.php");
                exit;
            }
        }
        $error = 'Incorrect email or password.';
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — EcoGrow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50 min-h-screen flex flex-col">
<?php require_once 'includes/navbar.php'; ?>

<div class="flex-1 flex items-center justify-center py-16 px-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <div class="text-center mb-8">
                <div class="text-5xl mb-3">🌿</div>
                <h1 class="text-2xl font-bold text-green-800">Welcome Back</h1>
                <p class="text-gray-400 text-sm mt-1">Sign in to your EcoGrow account</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-lg text-sm">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <?php render_flash(); ?>

            <form method="POST" action="login.php">
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                    <input type="email" name="email" required autofocus
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent"
                        placeholder="you@example.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input type="password" name="password" required
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent"
                        placeholder="••••••••">
                </div>
                <button type="submit"
                    class="w-full bg-green-600 text-white font-semibold py-3 rounded-xl hover:bg-green-700 transition text-sm">
                    Sign In
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-6">
                Don't have an account?
                <a href="register.php" class="text-green-600 font-semibold hover:underline">Sign Up</a>
            </p>
            <p class="text-center text-xs text-gray-400 mt-3">
                <a href="admin_login.php" class="hover:text-gray-600">Admin Login →</a>
            </p>
        </div>
    </div>
</div>

<footer class="bg-green-800 text-green-200 text-center py-4 text-sm">
    🌿 EcoGrow Nursery &copy; <?= date('Y') ?>
</footer>
</body>
</html>
