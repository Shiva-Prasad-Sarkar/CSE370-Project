<?php
session_start();
require_once 'config.php';
require_once 'includes/flash.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(trim($_POST['review'] ?? ''))) {
    $customer_id = (int)$_SESSION['customer_id'];
    $review      = trim($_POST['review']);

    // Insert with approved = 0 — admin must approve before it shows publicly
    $stmt = $conn->prepare("INSERT INTO customers_reviews (CustomerID, Comments, approved) VALUES (?, ?, 0)");
    $stmt->bind_param("is", $customer_id, $review);
    $stmt->execute();
    $stmt->close();

    set_flash('success', 'Thank you! Your review has been submitted and is awaiting admin approval.');
}

$conn->close();
header("Location: index.php");
exit;
