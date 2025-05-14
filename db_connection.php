<?php
$servername = "localhost";
$username = "root";
$password = ""; // Update if your MySQL root has a password
$dbname = "ecogrow";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
