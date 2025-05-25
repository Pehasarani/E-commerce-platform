<?php
session_start();
include('connection.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $customer = $result->fetch_assoc();
            if (password_verify($password, $customer['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $customer['id'];
                $_SESSION['user_name'] = $customer['username'];
                $_SESSION['user_email'] = $customer['email'];
                
                
                // Set success message
                $_SESSION['toast'] = [
                    'type' => 'error',
                    'message' => "Logged out successfully"
                ];
                
                header("Location: product_view.php");
                exit();
            } else {
                $_SESSION['toast'] = [
                    'type' => 'error',
                    'message' => "Error: Invalid Email or password. Please try again."
                ];
            }
        } else {
            $_SESSION['toast'] = [
                'type' => 'error',
                'message' => "Error: Invalid Email or password. Please try again."
            ];
        }
    } else {
        $_SESSION['toast'] = [
            'type' => 'error',
            'message' => "Error: Database connection failed. Please try again later."
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CeylonCart</title>

    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <style>
        /* General Styles */
        body {
            margin: 0;
            font-family: 'Koulen', sans-serif;
            background-color: #f35821;
            background-image: url('Images/Background\ Opacity.png');
            background-repeat: repeat;
            background-size: 50%;
            image-rendering: crisp-edges;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }

        .login-box {
            background-color: white;
            border-radius: 24px;
            padding: 32px;
            width: 100%;
            max-width: 448px;
        }

        .back-button {
            align-items: left;
            width: 79px;
            height: 27px;
        } 

        .logo-container {
            display: flex;
            justify-content: center;
            margin-bottom: 24px;
        }

        .logo {
            width: 120px;
            height: 85px;
        }

        .login-title {
            font-size: 29px;
            text-align: center;
            margin-bottom: 24px;
            font-family: 'Koulen', sans-serif;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 4px;
        }

        .email-input .form-input {
            width: 100%;
            padding: 12px;
            background-color: #f3f4f6;
            border-radius: 8px;
            border: none;
            font-family: sans-serif;
            box-sizing: border-box; 
            height: 48px;
        }   

        .password-input {
            position: relative;
            display: flex;
            align-items: center;
            border: 0px solid #ccc;
            border-radius: 8px;
            padding: 0px;
            background-color: #f3f4f6;
            height: 48px;
        }

        .password-input .form-input {
            width: 100%;
            padding: 12px;
            background-color: #f3f4f6;
            border-radius: 8px;
            border: none;
            font-family: sans-serif;
            box-sizing: border-box;
            height: 48px; 
        }

        .eye-icon {
            position: absolute;
            right: 10px;
            cursor: pointer;
            padding: 8px;
            z-index: 2
        }

        .eye-image {
            width: 20px;
            height: 20px;
            *padding: 10px;
            transition: opacity 0.3s ease;
        }

        .eye-icon[data-state="visible"] .eye-image {
            opacity: 0.1;
        }

        .login-button {
            width: 100%;
            background-color: #f35821;
            color: white;
            padding: 1px;
            border-radius: 8px;
            border: none;
            font-size: 24px;
            font-weight: 0;
            cursor: pointer;
            font-family: 'Koulen', sans-serif;
            box-sizing: border-box; 
        }

        .signup-text, .or-text, .supplier-text {
            text-align: center;
            font-size: 14px;
            margin-top: 16px;
        }

        .dont-have-acc {
            color: #2B2B2B;
            font-weight: 100;
            text-decoration: none;
        }

        .signup-link, .supplier-link {
            color: #f35821;
            font-weight: 100;
            text-decoration: none;
        }

        .or-text {
            margin: 8px 0;
        }

        .validation-message {
            color: #dc2626;
            font-size: 14px;
            margin: 8px 0;
            text-align: center;
            min-height: 20px;
            font-family: sans-serif;
        }
    </style>
</head>
<body>
    <div class="login-container">
            <div class="login-box">

                <a href="LoginType.php">
                    <img src="Images/back-icon.png" alt="Back" class="back-button">
                </a>

                <div class="logo-container">
                    <img src="Images/LogoL.png" alt="CeylonCart Logo" class="logo">
                </div>

                <h1 class="login-title">LOG IN</h1>

                <form class="login-form" action="login.php" method="POST">
              
                <div class="form-group email-input">
                        <label for="email" class="form-label">EMAIL</label>
                        <input type="email" id="email" name="email" placeholder="someone@gmail.com" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">PASSWORD</label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" placeholder="••••••••••" class="form-input" required>
                            <span class="eye-icon" data-state="hidden">
                                <img src="images/hide.png" alt="Toggle Password Visibility" class="eye-image">
                            </span>
                        </div>
                    </div>

                    <div class="validation-message" id="login-error">
                        <?php
                        if (isset($_SESSION['toast']) && $_SESSION['toast']['type'] === 'error') {
                            echo $_SESSION['toast']['message'];
                            unset($_SESSION['toast']);
                        }
                        ?>
                    </div>

                    <button type="submit" class="login-button">LOG IN</button>
                </form>
          

                <p class="signup-text">
                    DON'T YOU HAVE AN ACCOUNT? <a href="register.php" class="signup-link">SIGN UP</a>
                </p>

                <p class="or-text">OR</p>
                <p class="supplier-text">
                    <a href="../ceylon/supplierLogin.php" class="supplier-link">CONTINUE AS A SUPPLIER</a>
                </p>

        </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>

        $(document).ready(function() {
                
                $('.eye-icon').click(function() {
                    const passwordInput = $('#password');
                    const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
                    passwordInput.attr('type', type);
                    
                    const isVisible = type === 'text';
                    $(this).attr('data-state', isVisible ? 'visible' : 'hidden');
                });

                <?php if (isset($_SESSION['toast'])): ?>
                    Toastify({
                        text: "<?php echo $_SESSION['toast']['message']; ?>",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "<?php echo $_SESSION['toast']['type'] === 'success' ? 'linear-gradient(to right, #00b09b, #96c93d)' : 'linear-gradient(to right, #ff416c, #ff4b2b)'; ?>",
                        stopOnFocus: true
                    }).showToast();
                    <?php unset($_SESSION['toast']); ?>
                <?php endif; ?>
            });
            
    </script>
</body>
</html>
