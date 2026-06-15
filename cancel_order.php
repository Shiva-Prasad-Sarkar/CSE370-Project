<?php
session_start();
require_once 'config.php';
require_once 'includes/flash.php';

if (!isset($_SESSION['customer_id'])) { header("Location: login.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $customer_id = (int)$_SESSION['customer_id'];
    $order_id    = (int)$_POST['order_id'];

    // Only cancel pending orders belonging to this customer
    $stmt = $conn->prepare(
        "SELECT o.ID, o.Product_Id, o.Count FROM orders o
         WHERE o.ID = ? AND o.CustomerID = ?
         AND o.ID NOT IN (SELECT OrderID FROM admin_confirms_orders WHERE IsPending = 0)"
    );
    $stmt->bind_param("ii", $order_id, $customer_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($order) {
        // Restore product stock
        $stmt = $conn->prepare("UPDATE products SET Stock = Stock + ? WHERE ID = ?");
        $stmt->bind_param("ii", $order['Count'], $order['Product_Id']);
        $stmt->execute();
        $stmt->close();

        // Remove from admin_confirms_orders if a pending entry exists
        $stmt = $conn->prepare("DELETE FROM admin_confirms_orders WHERE OrderID = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();

        // Delete the order
        $stmt = $conn->prepare("DELETE FROM orders WHERE ID = ? AND CustomerID = ?");
        $stmt->bind_param("ii", $order_id, $customer_id);
        $stmt->execute();
        $stmt->close();

        set_flash('success', "Order #$order_id cancelled. Stock has been restored.");
    } else {
        set_flash('error', 'Cannot cancel this order — it may already be confirmed or not found.');
    }
}

$conn->close();
header("Location: user.php");
exit;
