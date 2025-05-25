<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
include('connection.php');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Get and sanitize form data
        $business_name = htmlspecialchars(trim($_POST['business_name']));
        $business_email = filter_var(trim($_POST['business_email']), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $business_address = htmlspecialchars(trim($_POST['business_address']));
        $contact_number = htmlspecialchars(trim($_POST['contact_number']));

        // Validate input
        if (empty($business_name) || empty($business_email) || empty($password) || empty($confirm_password) || empty($business_address) || empty($contact_number)) {
            throw new Exception("All fields are required");
        }

        if (!filter_var($business_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }

        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match");
        }

        if (!preg_match("/^[0-9]{10}$/", $contact_number)) {
            throw new Exception("Contact number must be exactly 10 digits");
        }

        // Check if email already exists
        $stmt = $conn->prepare("SELECT supplier_id FROM suppliers WHERE business_email = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt->bind_param("s", $business_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("Email already registered");
        }
        $stmt->close();

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new supplier
        $stmt = $conn->prepare("INSERT INTO suppliers (business_name, business_email, password, business_address, contact_number, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt->bind_param("sssss", $business_name, $business_email, $hashed_password, $business_address, $contact_number);
        
        if ($stmt->execute()) {
            $success = "Registration successful! You can now login.";
            // Clear form data
            $_POST = array();
        } else {
            throw new Exception("Registration failed: " . $stmt->error);
        }
        $stmt->close();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Registration</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="image-container">
            <img src="./images/image11.png" alt="Supplier Image">
        </div>
        <div class="form-container">
            <a href="supplierLogin.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>

            <div class="logo-container">
                <img src="./images/supplierLogo_v1.png" alt="Logo" class="logo">
                <h2>ELEVATE YOUR SHOPPING EXPERIENCE</h2>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form action="supplierRegister.php" method="post">
                <label for="business_name">BUSINESS NAME</label>
                <input type="text" 
                       id="business_name" 
                       name="business_name" 
                       placeholder="Enter your business name" 
                       pattern="[A-Za-z\s]+" 
                       title="Only letters and spaces allowed"
                       required>
                
                <label for="business_email">BUSINESS EMAIL</label>
                <input type="email" id="business_email" name="business_email" placeholder="company@gmail.com" required>
                
                <label for="password">PASSWORD</label>
                <input type="password" id="password" name="password" placeholder="***********" required>
                
                <label for="confirm_password">CONFIRM PASSWORD</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="***********" required>
                
                <label for="business_address">BUSINESS ADDRESS</label>
                <textarea id="business_address" name="business_address" placeholder="Enter your business address" required></textarea>
                
                <label for="contact_number">CONTACT NUMBER</label>
                <input type="tel" 
                       id="contact_number" 
                       name="contact_number" 
                       placeholder="Enter your contact number" 
                       pattern="[0-9-]{10,13}" 
                       title="Please enter a valid 10-digit phone number"
                       required>
                
                <button type="submit" class="register-btn">REGISTER</button>
                
                <p>ALREADY HAVE AN ACCOUNT? <a href="supplierLogin.php">LOGIN HERE</a></p>
            </form>
            <footer>
                <p>© CeylonCart 2025</p>
            </footer>
        </div>
    </div>
</body>
</html>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get form elements
    const form = document.querySelector('form');
    const businessNameInput = document.getElementById('business_name');
    const contactNumberInput = document.getElementById('contact_number');
    
    // Create error message elements
    const createErrorElement = () => {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.style.color = 'red';
        errorDiv.style.fontSize = '12px';
        errorDiv.style.marginTop = '5px';
        return errorDiv;
    };

    // Add error message elements after inputs
    const businessNameError = createErrorElement();
    const contactNumberError = createErrorElement();
    businessNameInput.parentNode.insertBefore(businessNameError, businessNameInput.nextSibling);
    contactNumberInput.parentNode.insertBefore(contactNumberError, contactNumberInput.nextSibling);

    // Validate business name (only letters and spaces)
    businessNameInput.addEventListener('input', function(e) {
        const value = e.target.value;
        const validValue = value.replace(/[^a-zA-Z\s]/g, '');
        
        if (value !== validValue) {
            e.target.value = validValue;
            businessNameError.textContent = 'Only letters (a-z, A-Z) and spaces are allowed';
            businessNameInput.classList.add('error-input');
        } else {
            businessNameError.textContent = '';
            businessNameInput.classList.remove('error-input');
        }
    });

    // Validate phone number (only numbers, max 10 digits)
    contactNumberInput.addEventListener('input', function(e) {
        const value = e.target.value;
        const validValue = value.replace(/\D/g, '').slice(0, 10);
        
        if (value !== validValue) {
            e.target.value = validValue;
            contactNumberError.textContent = value.length > 10 ? 
                'Phone number cannot exceed 10 digits' : 
                'Only numbers are allowed';
            contactNumberInput.classList.add('error-input');
        } else {
            contactNumberError.textContent = '';
            contactNumberInput.classList.remove('error-input');
        }

    });

    // Form submission validation
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validate business name
        if (!/^[a-zA-Z\s]+$/.test(businessNameInput.value.trim())) {
            businessNameError.textContent = 'Business name must contain only letters and spaces';
            businessNameInput.classList.add('error-input');
            isValid = false;
        }

        // Validate phone number
        const phoneValue = contactNumberInput.value.replace(/\D/g, '');
        if (phoneValue.length !== 10) {
            contactNumberError.textContent = 'Phone number must be exactly 10 digits';
            contactNumberInput.classList.add('error-input');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        }
    });
});
</script>

<style>
.error-input {
    border: 1px solid red !important;
}

.error-message {
    color: red;
    font-size: 12px;
    margin-top: 5px;
    display: block;
}

/* Improve input styling */
input[type="text"],
input[type="tel"] {
    color:black;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 100%;
    transition: border-color 0.3s ease;
}

input[type="text"]:focus,
input[type="tel"]:focus {
    color:black;
    outline: none;
}

/* Add visual feedback for valid input */
input:not(.error-input):valid {
    border-color: #4CAF50;
    background-color: #f8fff8;
}
</style>




