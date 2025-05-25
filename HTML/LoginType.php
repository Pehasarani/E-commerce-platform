<?php
session_start();

// Database connection settings
$dbHost = 'localhost';
$dbUsername = 'root';
$dbPassword = '';
$dbName = 'ceylon';

// Create connection
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Query to check if the user exists
    $sql = "SELECT * FROM customers WHERE username = '$username' AND password = '$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Login successful
        $_SESSION['customer_logged_in'] = true;
        $_SESSION['customer_username'] = $username;
        header("Location: customer-dashboard.php");
        exit;
    } else {
        echo "Invalid username or password";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CeylonCart Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
  <style>
    
  /* General Styles */
    body {
      margin: 0;
      font-family: 'Koulen', sans-serif;
      background-color: #f35821;
      background-image: url('Images/Background Opacity.png');
      background-repeat: repeat;
      background-size: 50%;
      image-rendering: crisp-edges;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 16px;

    }

    .container {
      text-align: center;
    }

    /* White Box */
    .content {
      background-color: white;
      border-radius: 24px; 
      padding: 24px; 
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); 
      max-width: 500px;
      width: 100%; 
    }

    /* Logo */
    .logo img {
      width: 80px; 
    }

    .logo h2 {
      margin: 8px 0; 
      font-size: 28px; 
      font-weight: normal;
      color: #333;
    }

    .logo p {
      font-size: 14px;
      margin: 0;
      color: #666;
    }

    /* Buttons */
    .buttons {
      margin-top: 20px;
      display: flex; 
      gap: 16px;
      justify-content: center;
    }

    .btn {
      padding: 8px 32px;
      font-size: 22px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      width: 220px;
      transition: opacity 0.3s ease;
      font-family: 'Koulen', sans-serif; 
      white-space: nowrap; 
    }

    .supplier-btn {
      background-color: #2b2b2b; 
      color: white;
    }

    .customer-btn {
      background-color: #F35821; 
      color: white; 
    }

    .customer-btn:hover, .supplier-btn:hover {
      opacity: 0.9;
    }
    </style> 
</head>
<body>
  <div class="container">
    <!-- White Box -->
    <div class="content">
      <!-- Sale Text -->
     

      <!-- Logo -->
      <div class="logo">
        <img src="Images/LogoL.png" alt="CeylonCart Logo">
        
        <p>ELEVATE YOUR SHOPPING EXPERIENCE</p>
      </div>

      <!-- Buttons -->
      <div class="buttons">
        <a href = "../ceylon/supplierLogin.php">        
          <button type="submit" class="btn supplier-btn">LOG IN AS A SUPPLIER</button>
        </a>
        <a href = "login.php">
          <button type="submit" class="btn customer-btn">LOG IN AS A CUSTOMER</button>
        </a>  
        </form>
      </div>
    </div>
  </div>
</body>
</html>