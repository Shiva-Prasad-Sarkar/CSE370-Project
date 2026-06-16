<?php
session_start();
require_once 'config.php';
require_once 'includes/flash.php';

$success = false;
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name))                                  $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))    $errors[] = 'A valid email is required.';
    if (empty($subject))                               $errors[] = 'Subject is required.';
    if (strlen($message) < 10)                         $errors[] = 'Message must be at least 10 characters.';

    if (!$errors) {
        $stmt = $conn->prepare(
            "INSERT INTO contact_messages (Name, Email, Subject, Message, CreatedAt)
             VALUES (?, ?, ?, ?, NOW())"
        );
        if ($stmt) {
            $stmt->bind_param("ssss", $name, $email, $subject, $message);
            $stmt->execute();
            $stmt->close();
        }
        $success = true;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us — EcoGrow</title>
    <meta name="description" content="Get in touch with EcoGrow Nursery. We're here to help with all your plant care and gardening needs.">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50 min-h-screen flex flex-col">
<?php require_once 'includes/navbar.php'; ?>

<!-- Header -->
<div class="bg-green-700 text-white py-12 px-4">
    <div class="max-w-5xl mx-auto">
        <h1 class="text-4xl font-bold mb-2">📬 Contact Us</h1>
        <p class="text-green-200 text-sm">We'd love to hear from you — drop us a message!</p>
    </div>
</div>

<div class="max-w-5xl mx-auto px-4 py-10 flex-1 w-full">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Contact Info -->
        <div class="space-y-5">
            <div class="bg-white rounded-xl border border-green-100 shadow-sm p-5">
                <h2 class="font-semibold text-gray-800 mb-4">Our Information</h2>
                <div class="space-y-4 text-sm text-gray-600">
                    <div class="flex gap-3">
                        <span class="text-xl">📍</span>
                        <div>
                            <p class="font-medium text-gray-800">Head Office</p>
                            <p>Plot 5, Road 103, Gulshan-2<br>Dhaka 1212, Bangladesh</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <span class="text-xl">📞</span>
                        <div>
                            <p class="font-medium text-gray-800">Phone</p>
                            <p>+880 1700-000000</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <span class="text-xl">✉️</span>
                        <div>
                            <p class="font-medium text-gray-800">Email</p>
                            <p>hello@ecogrow.com.bd</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <span class="text-xl">🕐</span>
                        <div>
                            <p class="font-medium text-gray-800">Office Hours</p>
                            <p>Saturday – Thursday<br>9:00 AM – 8:00 PM</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-green-700 rounded-xl p-5 text-white">
                <h3 class="font-semibold mb-2">Looking for a branch?</h3>
                <p class="text-green-200 text-sm mb-3">We have 6 locations across Bangladesh — Dhaka, Chattogram, and Sylhet.</p>
                <a href="about.php#branches" class="inline-block bg-white text-green-700 text-xs font-semibold px-4 py-2 rounded-lg hover:bg-green-50 transition">
                    View All Branches →
                </a>
            </div>
        </div>

        <!-- Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-green-100 shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-5">Send Us a Message</h2>

                <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 rounded-xl p-5 text-center mb-6">
                    <div class="text-4xl mb-2">✅</div>
                    <h3 class="font-semibold text-green-800 mb-1">Message Sent!</h3>
                    <p class="text-green-700 text-sm">Thank you for reaching out. We'll get back to you within 24 hours.</p>
                    <a href="contact.php" class="inline-block mt-4 text-green-600 text-sm hover:underline">Send another message</a>
                </div>
                <?php else: ?>

                <?php if ($errors): ?>
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-5">
                    <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
                        <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Your Name *</label>
                            <input type="text" name="name" required
                                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                placeholder="e.g. Rahim Uddin"
                                class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address *</label>
                            <input type="email" name="email" required
                                value="<?= htmlspecialchars($_POST['email'] ?? (isset($_SESSION['customer_id']) ? '' : '')) ?>"
                                placeholder="you@example.com"
                                class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Subject *</label>
                        <input type="text" name="subject" required
                            value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>"
                            placeholder="e.g. Question about a product"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Message *</label>
                        <textarea name="message" required rows="5"
                            placeholder="Write your message here…"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 resize-none"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>
                    <button type="submit"
                        class="w-full sm:w-auto bg-green-600 text-white px-8 py-3 rounded-xl hover:bg-green-700 transition font-semibold text-sm shadow-sm">
                        Send Message →
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<footer class="bg-green-900 text-green-300 text-center py-6 text-sm mt-auto">
    <p class="mb-2 text-white font-semibold">🌿 EcoGrow Nursery</p>
    <div class="flex justify-center gap-6 text-xs">
        <a href="index.php" class="hover:text-white transition">Home</a>
        <a href="product.php" class="hover:text-white transition">Products</a>
        <a href="events.php" class="hover:text-white transition">Events</a>
        <a href="about.php" class="hover:text-white transition">About</a>
    </div>
    <p class="mt-3 text-xs text-green-600">&copy; <?= date('Y') ?> EcoGrow Nursery. All rights reserved.</p>
</footer>

</body>
</html>
