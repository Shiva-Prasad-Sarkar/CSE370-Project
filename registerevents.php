<?php
session_start();
require_once 'config.php';
require_once 'includes/flash.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}
if (!isset($_GET['event_id'])) {
    header("Location: events.php");
    exit;
}

$customer_id = (int)$_SESSION['customer_id'];
$event_id    = (int)$_GET['event_id'];
$access_pass = "123";
$date        = date("Y-m-d");

// Check for duplicate
$stmt = $conn->prepare("SELECT COUNT(*) FROM customers_workshops WHERE CustomerID = ? AND WorkshopID = ?");
$stmt->bind_param("ii", $customer_id, $event_id);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count > 0) {
    set_flash('error', 'You are already registered for this event.');
    $conn->close();
    header("Location: events.php");
    exit;
}

$stmt = $conn->prepare("INSERT INTO customers_workshops (CustomerID, WorkshopID, AccessPass, Date) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiss", $customer_id, $event_id, $access_pass, $date);

if ($stmt->execute()) {
    set_flash('success', 'Successfully registered for the event!');
} else {
    set_flash('error', 'Registration failed. Please try again.');
}
$stmt->close();
$conn->close();

header("Location: events.php");
exit;
