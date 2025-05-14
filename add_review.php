<?php
session_start();

// Ensure that the user is logged in.
if (!isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit;
}

// Establish a database connection.
$conn = new mysqli("localhost", "root", "", "ecogrow");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the review was submitted.
if (isset($_POST['review'])) {
    // Sanitize the review input.
    $review = $conn->real_escape_string($_POST['review']);
    $customer_id = $_SESSION['customer_id'];

    // Insert the review into the Customers_Reviews table.
    $sql = "INSERT INTO Customers_Reviews (CustomerID, Comments) VALUES ('$customer_id', '$review')";

    if ($conn->query($sql) === TRUE) {
        // Redirect back to index.php after a successful insert.
        header("Location: index.php");
        exit;
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>
