<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecogrow";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password_input = $_POST['password'];
    
    
    $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ? AND password = ?");
    if (!$stmt) {
        die('SQL error: ' . $conn->error);
    }
    $stmt->bind_param("ss", $email, $password_input);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['customer_id'] = $user['ID']; // Consistent session variable name.
        echo "<script>alert('Login successful!'); window.location.href='index.php';</script>";
        exit();
    } else {
        echo "<script>alert('Incorrect email or password!'); window.location.href='login.html';</script>";
        exit();
    }
    // $stmt->close();
} else {
    echo "Form not submitted!";
}
$conn->close();
?>
