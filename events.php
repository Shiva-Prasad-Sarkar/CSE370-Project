<?php
session_start();

// Database connection parameters.
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecogrow";

// Create connection.
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to retrieve workshop details along with branch locations.
// GROUP_CONCAT combines multiple branch locations for a single workshop.
$sql = "SELECT 
            w.WID, 
            w.Topic, 
            w.Subject, 
            w.Date, 
            w.Type, 
            w.Points, 
            w.Price,
            GROUP_CONCAT(DISTINCT b.Location SEPARATOR ', ') AS Locations 
        FROM Workshops w 
        INNER JOIN Workshops_Branches wb ON w.WID = wb.WorkshopID 
        LEFT JOIN Branches b ON wb.BranchID = b.ID 
        GROUP BY w.WID
        ORDER BY w.Date ASC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Workshop Events</title>
    <style>
        /* Navigation Bar Styles */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #333;
            padding: 10px 20px;
        }
        .navbar a {
            color: #fff;
            text-decoration: none;
            margin-right: 15px;
            font-size: 16px;
        }
        .navbar .left-menu {
            display: flex;
            align-items: center;
        }
        .navbar .right-menu a {
            margin-left: 10px;
        }
        
        /* Container and Card Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f3f3f3;
        }
        h1 {
            text-align: center;
            margin-top: 20px;
        }
        .container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 300px;
            margin: 15px;
            padding: 15px;
            box-shadow: 1px 1px 7px rgba(0,0,0,0.1);
        }
        .card h3 {
            margin: 0 0 10px;
        }
        .card p {
            margin: 5px 0;
            font-size: 14px;
        }
        .register-btn {
            padding: 8px 16px;
            background-color: #008cba;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        .register-btn:hover {
            background-color: #006494;
        }
    </style>
    <script>
        // Set logged-in flag based on the session.
        var isLoggedIn = <?php echo isset($_SESSION['customer_id']) ? 'true' : 'false'; ?>;
        
        // When the register button is clicked, check if the user is logged in.
        function registerEvent(eventId) {
            if (!isLoggedIn) {
                alert('Please login to register');
                window.location.href = 'login.html';
            } else {
                window.location.href = 'registerevents.php?event_id=' + eventId;
            }
        }
    </script>
</head>
<body>
    <!-- Navigation Bar -->
    <div class="navbar">
        <div class="left-menu">
            <a href="index.php">Home</a>
            <a href="product.php">Products</a>
        </div>
        <div class="right-menu">
            <?php if (isset($_SESSION['customer_id'])) { ?>
                <a href="user.php">Profile</a>
            <?php } else { ?>
                <a href="login.html">Login/Register</a>
            <?php } ?>
        </div>
    </div>

    <h1>Workshop Events</h1>
    <div class="container">
        <?php 
        if ($result && $result->num_rows > 0) {
            // Loop through each workshop and display as a card.
            while ($row = $result->fetch_assoc()) {
                echo '<div class="card">';
                echo '<h3>' . htmlspecialchars($row['Topic']) . '</h3>';
                echo '<p><strong>Subject:</strong> ' . htmlspecialchars($row['Subject']) . '</p>';
                echo '<p><strong>Date:</strong> ' . htmlspecialchars($row['Date']) . '</p>';
                echo '<p><strong>Location:</strong> ' . htmlspecialchars($row['Locations']) . '</p>';
                echo '<p><strong>Type:</strong> ' . htmlspecialchars($row['Type']) . '</p>';
                if ($row['Type'] === 'Paid') {
                    echo '<p><strong>Price:</strong> $' . htmlspecialchars($row['Price']) . '</p>';
                } else {
                    echo '<p><strong>Price:</strong> Free</p>';
                }
                echo '<button class="register-btn" onclick="registerEvent(' . $row['WID'] . ')">Register</button>';
                echo '</div>';
            }
        } else {
            echo '<p>No events found.</p>';
        }
        ?>
    </div>
</body>
</html>
<?php 
$conn->close();
?>
