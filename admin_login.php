<?php
session_start();
$conn = new mysqli("localhost", "root", "", "ecogrow");

if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT ID, Name, Position, Email, Photo, Password FROM Admins WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $name, $position, $email, $photo, $hashed_password);
        $stmt->fetch();

        if ($password === $hashed_password) { // use password_verify() if hashed
            $_SESSION['admin'] = [
                'id' => $id,
                'name' => $name,
                'position' => $position,
                'email' => $email,
                'photo' => $photo
            ];
            header("Location: admin_dashboard.php");
            exit;
        } else {
            $message = "❌ Invalid password.";
        }
    } else {
        $message = "❌ Admin not found.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Admin Login</title>
  <style>
    body { font-family: Arial; background: #e0f7e9; padding: 50px; }
    .container { max-width: 400px; margin: auto; background: white; padding: 20px; border-radius: 10px; }
    input { width: 100%; padding: 10px; margin-bottom: 15px; }
    button { width: 100%; padding: 10px; background: #4caf50; color: white; border: none; }
    .message { text-align: center; color: red; }
  </style>
</head>
<body>
<div class="container">
  <h2>Admin Login</h2>
  <?php if ($message) echo "<div class='message'>$message</div>"; ?>
  <form method="post">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
  </form>
</div>
</body>
</html>
