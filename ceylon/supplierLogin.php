<?php
session_start();
include('connection.php');

// Check if already logged in
if (isset($_SESSION['supplier_id'])) {
    header("Location: supplierDashboard.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $business_email = trim($_POST['business_email']);
    $password = $_POST['password'];

    // Validate input
    if (empty($business_email) || empty($password)) {
        $error = "All fields are required";
    } else {
        // Prepare the SQL statement
        $stmt = $conn->prepare("SELECT supplier_id, business_name, password FROM suppliers WHERE business_email = ?");
        $stmt->bind_param("s", $business_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])|| $password == $row['password']) {
                // Set session variables
                $_SESSION['supplier_id'] = $row['supplier_id'];
                $_SESSION['business_name'] = $row['business_name'];
                $_SESSION['business_email'] = $business_email; // Make sure to set this
                
                // Redirect to dashboard
                header("Location: supplierDashboard.php");
                exit();
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Login</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>


<body>
    <div class="container">
        <div class="image-container">
            <img src="./images/image11.png" alt="Supplier Image">
        </div>
        <div class="form-container">
            <!-- Back link with icon -->
            <a href="../HTML/loginType.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Login As a Customer
            </a>
            <!-- Logo -->
            <div class="logo-container">
                <img src="./images/supplierLogo_v1.png" alt="Logo" class="logo">
                <h2>ELEVATE YOUR SHOPPING EXPERIENCE</h2>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            <form action="supplierLogin.php" method="post">
                <label for="business_email">BUSINESS EMAIL</label>
                <input type="email" id="business_email" name="business_email" placeholder="company@gmail.com" required>
                
                <label for="password">PASSWORD</label>
                <input type="password" id="password" name="password" placeholder="***********" required>
                
                <button type="submit" class="login-btn">LOG IN</button>
                
                <p>DON'T YOU HAVE A SUPPLIER ACCOUNT? <a href="supplierRegister.php">REGISTER HERE</a></p>
            </form>
            <footer>
                <p>© CeylonCart 2025</p>
            </footer>
        </div>
    </div>
</body>
</html>
