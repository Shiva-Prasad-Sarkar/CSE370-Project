<?php
session_start();

if (!isset($_SESSION['customer_id'])) {
    echo "<script>alert('Please login/register to order.'); window.location.href='login.html';</script>";
    exit();
}

$conn = new mysqli("localhost", "root", "", "ecogrow");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['product_id'])) {
    $customer_id = $_SESSION['customer_id'];
    $product_id = $conn->real_escape_string($_POST['product_id']);
    
    $sql = "INSERT INTO Cart (CustomerID, ProductID) 
            VALUES ('$customer_id', '$product_id') 
            ON DUPLICATE KEY UPDATE AddedOn = CURRENT_TIMESTAMP";
    
    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Successfully added to cart.'); window.location.href='product.php';</script>";
    } else {
        echo "<script>alert('Error adding to cart: " . $conn->error . "'); window.location.href='product.php';</script>";
    }
}

$conn->close();
?>
