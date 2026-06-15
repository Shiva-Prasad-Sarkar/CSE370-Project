<?php
session_start();
if (isset($_SESSION['admin'])) { header("Location: admin_dashboard.php"); exit; }

require_once 'config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT ID, Name, Position, Email, Photo, Password FROM admins WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($admin) {
        $ok = password_verify($pass, $admin['Password']);
        if (!$ok && $pass === $admin['Password']) {
            // Migrate plain-text to bcrypt
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE admins SET Password = ? WHERE ID = ?");
            $upd->bind_param("si", $hashed, $admin['ID']);
            $upd->execute();
            $upd->close();
            $ok = true;
        }
        if ($ok) {
            $_SESSION['admin'] = [
                'id'       => $admin['ID'],
                'name'     => $admin['Name'],
                'position' => $admin['Position'],
                'email'    => $admin['Email'],
                'photo'    => $admin['Photo'],
            ];
            $conn->close();
            header("Location: admin_dashboard.php");
            exit;
        }
        $message = 'Invalid password.';
    } else {
        $message = 'Admin not found.';
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — EcoGrow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-900 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <div class="text-5xl mb-3">🔐</div>
            <h1 class="text-2xl font-bold text-white">Admin Portal</h1>
            <p class="text-green-400 text-sm mt-1">EcoGrow Management System</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-8">
            <?php if ($message): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-3 mb-5 rounded-r-lg text-sm">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" required autofocus
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400"
                        placeholder="admin@example.com">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input type="password" name="password" required
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400"
                        placeholder="••••••••">
                </div>
                <button type="submit"
                    class="w-full bg-green-700 text-white font-semibold py-3 rounded-xl hover:bg-green-800 transition text-sm">
                    Sign In
                </button>
            </form>

            <p class="text-center mt-5 text-sm text-gray-400">
                <a href="index.php" class="hover:text-gray-600">← Back to main site</a>
            </p>
        </div>
    </div>
</body>
</html>
