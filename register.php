<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = ""; // XAMPP default
$database = "ecogrow";

$conn = new mysqli($servername, $username, $password, $database);

// Check DB connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Collect form data
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Basic validation
if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
    echo "<script>alert('All fields are required.'); window.history.back();</script>";
    exit();
}

if ($password !== $confirm_password) {
    echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
    exit();
}

// Check if email already exists
$checkEmailQuery = "SELECT * FROM Customers WHERE Email = ?";
$stmt = $conn->prepare($checkEmailQuery);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<script>alert('Email already registered! Please use a different one.'); window.history.back();</script>";
    exit();
}

// Insert into Customers
$insertQuery = "INSERT INTO Customers (Name, Phone, Email, Type, Password) VALUES (?, ?, ?, 'Registered', ?)";
$stmt = $conn->prepare($insertQuery);
$stmt->bind_param("ssss", $name, $phone, $email, $password);

if ($stmt->execute()) {
    echo "<script>alert('Registration successful! You can now login.'); window.location.href='login.html';</script>";
} else {
    echo "<script>alert('Error: " . $stmt->error . "'); window.history.back();</script>";
}

$stmt->close();
$conn->close();
?>
