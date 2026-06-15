<?php
session_start();
require_once 'config.php';
require_once 'includes/flash.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $customer_id = (int)$_SESSION['customer_id'];
    $product_id  = (int)$_POST['product_id'];

    $stmt = $conn->prepare(
        "INSERT INTO Cart (CustomerID, ProductID) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE AddedOn = CURRENT_TIMESTAMP"
    );
    $stmt->bind_param("ii", $customer_id, $product_id);
    $stmt->execute();
    $stmt->close();

    set_flash('success', 'Product added to your cart!');
}

$conn->close();
header("Location: product.php");
exit;
