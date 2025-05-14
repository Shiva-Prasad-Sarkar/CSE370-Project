<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['customer_id'])) {
    echo "<script>
            alert('Please login to register');
            window.location.href = 'login.html';
          </script>";
    exit();
}

// Validate that an event ID has been provided
if (!isset($_GET['event_id'])) {
    echo "<script>
            alert('Invalid event selection');
            window.location.href = 'events.php';
          </script>";
    exit();
}

$customer_id = $_SESSION['customer_id'];
$event_id = intval($_GET['event_id']);
$access_pass = "123"; // Fixed access pass
$registration_date = date("Y-m-d"); // Current date

// Database connection parameters.
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "ecogrow";

// Create the database connection.
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check for duplicate registration before inserting.
$check_sql = "SELECT * FROM Customers_Workshops WHERE CustomerID = ? AND WorkshopID = ?";
$stmt_check = $conn->prepare($check_sql);
$stmt_check->bind_param("ii", $customer_id, $event_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    // Duplicate registration found; alert the user.
    echo "<script>
            alert('You have already registered for this event.');
            window.location.href = 'events.php';
          </script>";
    $stmt_check->close();
    $conn->close();
    exit();
}

$stmt_check->close();

// Prepare the SQL statement for inserting registration data.
$sql = "INSERT INTO Customers_Workshops (CustomerID, WorkshopID, AccessPass, Date) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// Bind the parameters. (Integer, integer, string, string)
$stmt->bind_param("iiss", $customer_id, $event_id, $access_pass, $registration_date);

// Execute the statement and provide feedback to the user.
if ($stmt->execute()) {
    echo "<script>
            alert('Registration successful!');
            window.location.href = 'events.php';
          </script>";
} else {
    echo "<script>
            alert('Registration failed. Please try again later.');
            window.location.href = 'events.php';
          </script>";
}

// Close the statement and connection.
$stmt->close();
$conn->close();
?>
