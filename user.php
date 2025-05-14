<?php
session_start();

// If user is not logged in, redirect to login page
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.html");
    exit();
}

// Handle logout request
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.html");
    exit();
}

// Connect to MySQL database
$mysqli = new mysqli("localhost", "root", "", "ecogrow");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$user_id = $_SESSION['customer_id'];

// Fetch customer data from the Customers table
$stmt = $mysqli->prepare("SELECT ID, Name, Email, Password, Type, Points FROM Customers WHERE ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo "User not found.";
    exit();
}
$user = $result->fetch_assoc();
$stmt->close();

$message = "";

// Handle the change-password form submission using plain text comparison
if (isset($_POST['change_password'])) {
    // Make sure all fields are provided
    $current_password     = trim($_POST['current_password'] ?? '');
    $new_password         = trim($_POST['new_password'] ?? '');
    $confirm_new_password = trim($_POST['confirm_new_password'] ?? '');

    if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
        $message = "Please fill all the fields.";
    } elseif ($new_password !== $confirm_new_password) {
        $message = "New password and confirmation do not match.";
    } else {
        // Only allow password changes for Registered users
        if ($user['Type'] !== 'Registered') {
            $message = "Guests cannot change their password.";
        } else {
            // Compare passwords in plain text
            if ($current_password === $user['Password']) {
                $stmt = $mysqli->prepare("UPDATE Customers SET Password = ? WHERE ID = ?");
                $stmt->bind_param("si", $new_password, $user_id);
                if ($stmt->execute()) {
                    $message = "Password updated successfully.";
                    $user['Password'] = $new_password;
                } else {
                    $message = "Failed to update password. Please try again later.";
                }
                $stmt->close();
            } else {
                $message = "Current password is incorrect.";
            }
        }
    }
}

// Retrieve cart items for the customer by joining Cart with Products
$stmt_cart = $mysqli->prepare("SELECT c.ProductID, c.AddedOn, p.Name, p.Price, p.Stock 
                               FROM Cart c 
                               JOIN Products p ON c.ProductID = p.ID 
                               WHERE c.CustomerID = ?");
$stmt_cart->bind_param("i", $user_id);
$stmt_cart->execute();
$cart_result = $stmt_cart->get_result();
$cart_items = [];
while ($row = $cart_result->fetch_assoc()) {
    $cart_items[] = $row;
}
$stmt_cart->close();

// Retrieve confirmed orders for this customer.
// We left join the Admin_Confirms_Orders table to get status info.
// If no row exists for an order in Admin_Confirms_Orders, we treat it as pending.
$stmt_orders = $mysqli->prepare("
    SELECT o.ID AS order_id,
           o.Date,
           o.Bill,
           o.Count,
           o.Address,
           COALESCE(a.IsPending, 1) AS IsPending,
           GROUP_CONCAT(p.Name SEPARATOR ', ') AS ProductNames
    FROM Orders o
    LEFT JOIN Admin_Confirms_Orders a ON o.ID = a.OrderID
    JOIN Products p ON o.Product_Id = p.ID
    WHERE o.CustomerID = ?
    GROUP BY o.ID
    ORDER BY o.Date DESC
");

$stmt_orders->bind_param("i", $user_id);
$stmt_orders->execute();
$result_orders = $stmt_orders->get_result();
$orders = [];
while ($order = $result_orders->fetch_assoc()) {
    $orders[] = $order;
}
$stmt_orders->close();

// Retrieve registered events for the customer by joining Workshops with Customers_Workshops
$stmt_events = $mysqli->prepare("
    SELECT w.WID, w.Topic, w.Subject, w.Date, w.Type, w.Price, cw.Date AS RegistrationDate
    FROM Workshops w
    JOIN Customers_Workshops cw ON w.WID = cw.WorkshopID
    WHERE cw.CustomerID = ?
    ORDER BY w.Date ASC
");
$stmt_events->bind_param("i", $user_id);
$stmt_events->execute();
$result_events = $stmt_events->get_result();
$registered_events = [];
while ($event = $result_events->fetch_assoc()) {
    $registered_events[] = $event;
}
$stmt_events->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile - EcoGrow</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .profile, .cart, .password-change, .orders, .registered-events { border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        .message { color: red; }
        form { margin-top: 10px; }
        .navigation-buttons { text-align: center; margin-top: 20px; }
        .navigation-buttons button { margin: 0 10px; padding: 10px 20px; font-size: 1rem; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($user['Name']); ?></h1>

    <!-- Profile Information -->
    <div class="profile">
        <h2>Profile Information</h2>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['Name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['Email']); ?></p>
        <p><strong>Points:</strong> <?php echo $user['Points']; ?></p>
    </div>

    <!-- Change Password Section -->
    <div class="password-change">
        <h2>Change Password</h2>
        <?php
        if (!empty($message)) {
            echo "<p class='message'>" . htmlspecialchars($message) . "</p>";
        }
        if ($user['Type'] === 'Registered') { 
        ?>
        <form method="post" action="">
            <label for="current_password">Current Password:</label><br>
            <input type="password" name="current_password" id="current_password" required><br><br>

            <label for="new_password">New Password:</label><br>
            <input type="password" name="new_password" id="new_password" required><br><br>

            <label for="confirm_new_password">Confirm New Password:</label><br>
            <input type="password" name="confirm_new_password" id="confirm_new_password" required><br><br>

            <input type="submit" name="change_password" value="Change Password">
        </form>
        <?php 
        } else { 
            echo "<p>You are a guest user, so the password change option is not available.</p>";
        } 
        ?>
    </div>

    <!-- Cart Section -->
    <div class="cart">
        <h2>Your Cart</h2>
        <?php if (count($cart_items) > 0) { ?>
        <table>
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Added On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['ProductID']); ?></td>
                    <td><?php echo htmlspecialchars($item['Name']); ?></td>
                    <td>$<?php echo number_format($item['Price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($item['Stock']); ?></td>
                    <td><?php echo htmlspecialchars($item['AddedOn']); ?></td>
                    <td>
                        <!-- Redirects to order.php with the product id -->
                        <a href="order.php?productid=<?php echo urlencode($item['ProductID']); ?>">Order</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php } else { ?>
            <p>Your cart is empty.</p>
        <?php } ?>
    </div>

    <!-- Confirmed Orders Section -->
    <div class="orders">
        <h2>Your Confirmed Orders</h2>
        <?php if (count($orders) > 0) { ?>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Product Names</th>
                    <th>Date</th>
                    <th>Bill</th>
                    <th>Count</th>
                    <th>Address</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order) { 
                    // Determine status based on IsPending: 1 = Pending, 0 = Placed.
                    $status = ($order['IsPending'] == 1) ? "Pending" : "Placed";
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                    <td><?php echo htmlspecialchars($order['ProductNames']); ?></td>

                    <td><?php echo htmlspecialchars($order['Date']); ?></td>
                    <td>$<?php echo number_format($order['Bill'], 2); ?></td>
                    <td><?php echo htmlspecialchars($order['Count']); ?></td>
                    <td><?php echo htmlspecialchars($order['Address']); ?></td>
                    <td><?php echo $status; ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php } else { ?>
            <p>You have no confirmed orders.</p>
        <?php } ?>
    </div>
    
    <!-- Registered Events Section -->
    <div class="registered-events">
        <h2>Your Registered Events</h2>
        <?php if (count($registered_events) > 0) { ?>
        <table>
            <thead>
                <tr>
                    <th>Event ID</th>
                    <th>Topic</th>
                    <th>Subject</th>
                    <th>Event Date</th>
                    <th>Type</th>
                    <th>Price</th>
                    <th>Registered On</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registered_events as $event) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($event['WID']); ?></td>
                    <td><?php echo htmlspecialchars($event['Topic']); ?></td>
                    <td><?php echo htmlspecialchars($event['Subject']); ?></td>
                    <td><?php echo htmlspecialchars($event['Date']); ?></td>
                    <td><?php echo htmlspecialchars($event['Type']); ?></td>
                    <td>
                        <?php 
                        if($event['Type'] === 'Paid'){
                            echo '$' . number_format($event['Price'], 2);
                        } else {
                            echo 'Free';
                        }
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($event['RegistrationDate']); ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php } else { ?>
            <p>You have not registered for any events yet.</p>
        <?php } ?>
    </div>

    <!-- Navigation Buttons -->
    <div class="navigation-buttons">
        <button onclick="window.location.href='product.php'">Products</button>
        <button onclick="window.location.href='events.php'">Workshops</button>
    </div>

    <!-- Logout Button -->
    <form method="post" action="">
        <input type="submit" name="logout" value="Logout">
    </form>
</body>
</html>

<?php
$mysqli->close();
?>
