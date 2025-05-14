<?php
session_start();

// Establish a database connection.
$conn = new mysqli("localhost", "root", "", "ecogrow");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve branch information.
$sql = "SELECT * FROM Branches ORDER BY ID ASC";
$result = $conn->query($sql);

// Retrieve reviews by joining Customers_Reviews with Customers table.
$sqlReviews = "SELECT CR.Comments, C.Name 
               FROM Customers_Reviews CR 
               JOIN Customers C ON CR.CustomerID = C.ID 
               ORDER BY CR.CustomerID DESC";
$resultReviews = $conn->query($sqlReviews);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EcoGrow Nursery Home</title>
    <style>
        /* General Body Styles with background photo */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: url('https://swanhose.com/cdn/shop/articles/water-plant-growth.jpg?v=1683652693') no-repeat center center fixed;
            background-size: cover;
        }
        /* Navigation Bar Styles */
        .navbar {
            background-color: #4CAF50;
            overflow: hidden;
            padding: 10px 20px;
        }
        .navbar a {
            color: #fff;
            text-decoration: none;
            margin-right: 20px;
            font-size: 16px;
            display: inline-block;
            padding: 6px 12px;
        }
        .navbar a:hover {
            background-color: #45a049;
            border-radius: 4px;
        }
        /* Animated Clipboard Icon */
        .clipboard {
            width: 50px;
            height: 50px;
            background: url('https://static.vecteezy.com/system/resources/previews/048/343/317/non_2x/cartoon-plant-logo-in-a-pot-with-a-happy-face-png.png') no-repeat center center;
            background-size: contain;
            position: fixed;
            top: 20px;
            right: 20px;
            
        }
        @keyframes clipboard-slide {
            from { transform: translateY(0); }
            to { transform: translateY(15px); }
        }
        /* Section Containers with slight transparency */
        .section-container {
            background-color: rgba(255, 255, 255, 0.85);
            padding: 20px;
            margin: 30px auto;
            max-width: 1000px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }
        /* Welcome Section Styles */
        .welcome-section {
            text-align: center;
            color: #333;
        }
        /* Branches Section Styles */
        .branches-container h2 {
            text-align: center;
            color: #4CAF50;
        }
        .branch-card {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 15px auto;
            max-width: 500px;
            padding: 15px;
        }
        .branch-card h3 {
            margin-top: 0;
            color: #333;
        }
        .branch-card p {
            margin: 5px 0;
            color: #555;
        }
        /* Reviews Section Styles */
        .reviews-container h2 {
            text-align: center;
            color: #4CAF50;
        }
        .review-card {
            border-bottom: 1px dashed #ccc;
            margin-bottom: 15px;
            padding-bottom: 15px;
        }
        .review-card:last-child {
            border-bottom: none;
        }
        .review-card p {
            margin: 5px 0;
            color: #555;
        }
        /* Review Form Styles */
        .review-form {
            margin-top: 20px;
            text-align: center;
        }
        .review-form textarea {
            width: 80%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            resize: vertical;
            font-size: 14px;
        }
        .review-form button {
            padding: 10px 20px;
            margin-top: 10px;
            background-color: #4CAF50;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 16px;
            border-radius: 4px;
        }
        .review-form button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <!-- Animated Clipboard Icon -->
    <div class="clipboard"></div>
    
    <!-- Navigation Bar -->
    <div class="navbar">
        <a href="index.php">Home</a>
        <?php if (isset($_SESSION['customer_id'])): ?>
            <a href="user.php">Profile</a>
        <?php else: ?>
            <a href="login.html">Login/Register</a>
        <?php endif; ?>
        <a href="product.php">Products</a>
        <a href="events.php">Workshops/Events</a>
    </div>

    <!-- Welcome Section -->
    <div class="section-container welcome-section">
        <h1>Welcome to EcoGrow Nursery</h1>
        <p>Discover our branches across the region.</p>
    </div>
    
    <!-- Branches Section -->
    <div class="section-container branches-container">
        <h2>Our Branches</h2>
        <?php
        if ($result && $result->num_rows > 0) {
            while ($branch = $result->fetch_assoc()) {
                echo '<div class="branch-card">';
                echo '<h3>' . htmlspecialchars($branch['Name']) . '</h3>';
                echo '<p><strong>Location:</strong> ' . htmlspecialchars($branch['Location']) . '</p>';
                echo '<p><strong>Ratings:</strong> ' . htmlspecialchars($branch['Ratings']) . '</p>';
                echo '<p><strong>Manager:</strong> ' . htmlspecialchars($branch['Manager']) . '</p>';
                echo '<p>' . nl2br(htmlspecialchars($branch['Details'])) . '</p>';
                echo '</div>';
            }
        } else {
            echo "<p>No branches found.</p>";
        }
        ?>
    </div>
    
    <!-- Reviews Section -->
    <div class="section-container reviews-container">
        <h2>Customer Reviews</h2>
        <?php
        if ($resultReviews && $resultReviews->num_rows > 0) {
            while ($review = $resultReviews->fetch_assoc()) {
                echo '<div class="review-card">';
                echo '<p><strong>' . htmlspecialchars($review['Name']) . ':</strong></p>';
                echo '<p>' . nl2br(htmlspecialchars($review['Comments'])) . '</p>';
                echo '</div>';
            }
        } else {
            echo "<p>No reviews yet. Be the first to share your experience!</p>";
        }
        ?>
        
        <!-- Review Submission Form (only for logged-in customers) -->
        <?php if (isset($_SESSION['customer_id'])): ?>
            <div class="review-form">
                <form method="POST" action="add_review.php">
                    <textarea name="review" placeholder="Share your experience at EcoGrow Nursery" required></textarea><br>
                    <button type="submit">Submit Review</button>
                </form>
            </div>
        <?php else: ?>
            <p style="text-align: center;">Please <a href="login.html">log in</a> to add a review.</p>
        <?php endif; ?>
    </div>
    
    <?php
    // Close the database connection.
    $conn->close();
    ?>
</body>
</html>
