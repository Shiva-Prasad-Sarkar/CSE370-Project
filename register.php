<?php
session_start();
if (isset($_SESSION['customer_id'])) { header("Location: user.php"); exit; }

require_once 'config.php';
require_once 'includes/flash.php';

$error  = '';
$values = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $values = compact('name', 'email', 'phone');

    if (empty($name) || empty($email) || empty($phone) || empty($pass) || empty($confirm)) {
        $error = 'All fields are required.';
    } elseif ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $stmt = $conn->prepare("SELECT ID FROM customers WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'An account with this email already exists.';
            $stmt->close();
        } else {
            $stmt->close();
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO customers (Name, Phone, Email, Type, Password) VALUES (?, ?, ?, 'Registered', ?)");
            $stmt->bind_param("ssss", $name, $phone, $email, $hashed);
            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                set_flash('success', 'Account created! You can now sign in.');
                header("Location: login.php");
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
                $stmt->close();
            }
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — EcoGrow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50 min-h-screen flex flex-col">
<?php require_once 'includes/navbar.php'; ?>

<div class="flex-1 flex items-center justify-center py-16 px-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <div class="text-center mb-8">
                <div class="text-5xl mb-3">🌱</div>
                <h1 class="text-2xl font-bold text-green-800">Create Account</h1>
                <p class="text-gray-400 text-sm mt-1">Join the EcoGrow community</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-lg text-sm">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                    <input type="text" name="name" required autofocus
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400"
                        placeholder="John Doe"
                        value="<?= htmlspecialchars($values['name'] ?? '') ?>">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                    <input type="email" name="email" required
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400"
                        placeholder="you@example.com"
                        value="<?= htmlspecialchars($values['email'] ?? '') ?>">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                    <input type="tel" name="phone" required
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400"
                        placeholder="01XXXXXXXXX"
                        value="<?= htmlspecialchars($values['phone'] ?? '') ?>">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input type="password" name="password" required
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400"
                        placeholder="At least 6 characters">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                    <input type="password" name="confirm_password" required
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400"
                        placeholder="Repeat password">
                </div>
                <button type="submit"
                    class="w-full bg-green-600 text-white font-semibold py-3 rounded-xl hover:bg-green-700 transition text-sm">
                    Create Account
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-6">
                Already have an account?
                <a href="login.php" class="text-green-600 font-semibold hover:underline">Sign In</a>
            </p>
        </div>
    </div>
</div>

<footer class="bg-green-800 text-green-200 text-center py-4 text-sm">
    🌿 EcoGrow Nursery &copy; <?= date('Y') ?>
</footer>
</body>
</html>
