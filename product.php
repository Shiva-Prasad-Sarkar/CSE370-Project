<?php
session_start();
$loggedIn = isset($_SESSION['customer_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>EcoGrow Products</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root {
      --green-dark: #2f5d50;
      --green-medium: #4caf50;
      --green-light: #a8d5ba;
      --bg-light: rgb(147, 221, 131);
      --text-dark: #1b4332;
      --text-light: #555;
      --card-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
    }
    
    * {
      box-sizing: border-box;
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      padding: 0;
    }
    
    body {
      background-color: var(--bg-light);
      padding: 40px 5%;
    }
    
    header {
      position: relative;
      padding: 20px 0;
      text-align: center;
    }
    
    /* Navigation buttons on the top-left */
    .nav-left {
      position: absolute;
      top: 20px;
      left: 20px;
    }
    .nav-btn {
      background: var(--green-dark);
      color: #fff;
      padding: 6px 12px;
      text-decoration: none;
      border-radius: 6px;
      margin-right: 10px;
      font-size: 1rem;
    }
    
    header h1 {
      text-align: center;
      color: var(--green-dark);
      font-size: 3rem;
      margin-bottom: 20px;
    }
    
    .profile-btn {
      position: absolute;
      top: 20px;
      right: 20px;
      padding: 8px 12px;
      border: none;
      border-radius: 6px;
      background: var(--green-dark);
      color: #fff;
      cursor: pointer;
    }
    
    .filter-bar {
      margin-bottom: 30px;
    }
    
    .filter-form {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 10px;
    }
    
    .filter-left select,
    .filter-left input[type="submit"] {
      padding: 8px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }
    
    .filter-right {
      margin-left: auto;
    }
    
    .filter-right input[type="text"] {
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 6px;
      min-width: 250px;
    }
    
    .product-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 30px;
    }
    
    .product-card {
      background: #fff;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: var(--card-shadow);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      display: flex;
      flex-direction: column;
      padding-bottom: 10px;
    }
    
    .product-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 16px 32px rgba(0, 0, 0, 0.12);
    }
    
    .product-card img {
      width: 100%;
      height: 200px;
      object-fit: cover;
    }
    
    .product-info {
      padding: 16px 20px;
      flex: 1;
    }
    
    .product-info h2 {
      font-size: 1.4rem;
      color: var(--text-dark);
      margin-bottom: 8px;
    }
    
    .price {
      font-size: 1.2rem;
      font-weight: bold;
      color: var(--green-medium);
      margin-bottom: 10px;
    }
    
    .description {
      font-size: 0.95rem;
      color: var(--text-light);
      margin-bottom: 12px;
    }
    
    .product-meta {
      font-size: 0.85rem;
      color: #444;
      margin-bottom: 5px;
    }
    
    .out-of-stock-msg {
      color: red;
      font-weight: bold;
      margin: 10px 0;
      font-size: 0.95rem;
    }
    
    .add-to-cart-btn {
      padding: 8px 12px;
      margin: 10px 20px 0;
      border: none;
      border-radius: 6px;
      background: var(--green-medium);
      color: #fff;
      cursor: pointer;
      font-size: 1rem;
    }
    
    .add-to-cart-btn:hover {
      background: #3e8e41;
    }
    
    footer {
      margin-top: 60px;
      text-align: center;
      font-size: 0.9rem;
      color: #888;
    }
  </style>
</head>
<body>
  <header>
    <!-- Navigation Buttons on the left -->
    <div class="nav-left">
      <a href="index.php" class="nav-btn">Home</a>
      <a href="events.php" class="nav-btn">Workshop</a>
    </div>
    <h1>🌿 Explore EcoGrow Products</h1>
    <?php if ($loggedIn): ?>
      <button class="profile-btn" type="button" onclick="location.href='user.php'">My Profile</button>
    <?php endif; ?>
  </header>
  
  <div class="filter-bar">
    <form action="" method="get" class="filter-form">
      <div class="filter-left">
        <select name="sort">
          <option value="">Sort By</option>
          <option value="name_asc" <?php if(isset($_GET['sort']) && $_GET['sort'] == 'name_asc') echo 'selected'; ?>>Name Ascending</option>
          <option value="name_desc" <?php if(isset($_GET['sort']) && $_GET['sort'] == 'name_desc') echo 'selected'; ?>>Name Descending</option>
          <option value="price_asc" <?php if(isset($_GET['sort']) && $_GET['sort'] == 'price_asc') echo 'selected'; ?>>Price Low to High</option>
          <option value="price_desc" <?php if(isset($_GET['sort']) && $_GET['sort'] == 'price_desc') echo 'selected'; ?>>Price High to Low</option>
        </select>
        
        <select name="category">
          <option value="">Filter by Category</option>
          <option value="Indoor" <?php if(isset($_GET['category']) && $_GET['category'] == 'Indoor') echo 'selected'; ?>>Indoor</option>
          <option value="Outdoor" <?php if(isset($_GET['category']) && $_GET['category'] == 'Outdoor') echo 'selected'; ?>>Outdoor</option>
        </select>
        
        <select name="limit">
          <option value="">Limit Results</option>
          <option value="5" <?php if(isset($_GET['limit']) && $_GET['limit'] == '5') echo 'selected'; ?>>5</option>
          <option value="10" <?php if(isset($_GET['limit']) && $_GET['limit'] == '10') echo 'selected'; ?>>10</option>
          <option value="15" <?php if(isset($_GET['limit']) && $_GET['limit'] == '15') echo 'selected'; ?>>15</option>
          <option value="20" <?php if(isset($_GET['limit']) && $_GET['limit'] == '20') echo 'selected'; ?>>20</option>
          <option value="25" <?php if(isset($_GET['limit']) && $_GET['limit'] == '25') echo 'selected'; ?>>25</option>
          <option value="30" <?php if(isset($_GET['limit']) && $_GET['limit'] == '30') echo 'selected'; ?>>30</option>
          <option value="40" <?php if(isset($_GET['limit']) && $_GET['limit'] == '40') echo 'selected'; ?>>40</option>
        </select>
        
        <input type="submit" value="Apply">
      </div>
      <div class="filter-right">
        <input type="text" name="search" placeholder="Search products by name" 
               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
      </div>
    </form>
  </div>
  
  <section class="product-grid">
    <?php
    // Connect to the database
    $conn = new mysqli("localhost", "root", "", "ecogrow");
    if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
    }
    
    // Build the base query
    $sql = "SELECT products.ID, Name, Category, SubType, Price, Stock, Ratings, Details, product_photos.Photo 
            FROM products 
            LEFT JOIN product_photos ON products.ID = product_photos.ProductID";
    
    $whereClauses = [];
    
    // Apply category filter if set
    if (isset($_GET['category']) && $_GET['category'] != '') {
      $category = $conn->real_escape_string($_GET['category']);
      $whereClauses[] = "Category = '$category'";
    }
    
    // Apply search filter if set
    if (isset($_GET['search']) && $_GET['search'] != '') {
      $search = $conn->real_escape_string($_GET['search']);
      $whereClauses[] = "Name LIKE '%$search%'";
    }
    
    if (count($whereClauses) > 0) {
      $sql .= " WHERE " . implode(" AND ", $whereClauses);
    }
    
    // Sorting
    if (isset($_GET['sort'])) {
      switch ($_GET['sort']) {
        case 'name_asc': 
          $sql .= " ORDER BY Name ASC"; 
          break;
        case 'name_desc': 
          $sql .= " ORDER BY Name DESC"; 
          break;
        case 'price_asc': 
          $sql .= " ORDER BY Price ASC"; 
          break;
        case 'price_desc': 
          $sql .= " ORDER BY Price DESC"; 
          break;
      }
    }
    
    // Limit
    if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
      $limit = (int)$_GET['limit'];
      $sql .= " LIMIT $limit";
    }
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
        echo "<div class='product-card'>";
        if (!empty($row['Photo'])) {
          echo "<img src='" . htmlspecialchars($row['Photo']) . "' alt='Product Image'>";
        } else {
          echo "<img src='default_image.jpg' alt='Default Image'>";
        }
        echo "<div class='product-info'>";
        echo "<h2>" . htmlspecialchars($row['Name']) . "</h2>";
        echo "<p class='price'>$" . number_format($row['Price'], 2) . "</p>";
        echo "<p class='description'>" . htmlspecialchars($row['Details']) . "</p>";
        echo "<p class='product-meta'>Category: " . htmlspecialchars($row['Category']) . "</p>";
        echo "<p class='product-meta'>SubType: " . htmlspecialchars($row['SubType']) . "</p>";
        if ($row['Stock'] <= 0) {
          echo "<p class='out-of-stock-msg'>Out of Stock</p>";
        }
        
        // Display an Add to Cart button if the user is logged in
        if ($loggedIn) {
          echo "<form action='add_to_cart.php' method='post'>";
          echo "<input type='hidden' name='product_id' value='" . htmlspecialchars($row['ID']) . "'>";
          echo "<button type='submit' class='add-to-cart-btn'>Add to Cart</button>";
          echo "</form>";
        } else {
          echo "<button type='button' class='add-to-cart-btn' onclick=\"alert('Please login/register to order.');\">Add to Cart</button>";
        }
        echo "</div></div>";
      }
    } else {
      echo "No products found.";
    }
    
    $conn->close();
    ?>
  </section>
  
  <footer>
    <p>EcoGrow &copy; 2025 | All Rights Reserved</p>
  </footer>
</body>
</html>
