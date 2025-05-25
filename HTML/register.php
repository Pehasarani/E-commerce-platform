<?php
session_start();
include('connection.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];  // Changed from 'name' to match your form field
    $email = $_POST['email'];
    $phone_personal = $_POST['phone-personal'];
    $phone_work = $_POST['phone-work'];  // Added missing semicolon
    $nic = $_POST['nic'];  // Added missing semicolon
    $dob = $_POST['dob'];
    $address = $_POST['address'];
    $postal_code = $_POST['postal-code'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $country = $_POST['country'];  // Added missing semicolon
    $created_at = date('Y-m-d H:i:s');
    $updated_at = date('Y-m-d H:i:s');

    // Validate input
    $errors = [];

    // Validate date of birth (must be before 2020)
    $dob_timestamp = strtotime($dob);
    $year_2020 = strtotime('2020-01-01');
    if ($dob_timestamp >= $year_2020) {
        $errors[] = "Date of birth must be before 2020";
    }

    // Validate NIC (12 characters)
    if (strlen($nic) !== 12) {
        $errors[] = "NIC must be exactly 12 characters";
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    // Validate phone numbers (10 digits)
    if (!preg_match('/^\d{10}$/', $phone_personal)) {
        $errors[] = "Personal phone number must be exactly 10 digits";
    }
    if (!preg_match('/^\d{10}$/', $phone_work)) {
        $errors[] = "Work phone number must be exactly 10 digits";
    }

    // Validate password
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    if (empty($errors)) {
        // Check if email already exists
        $check_email = $conn->prepare("SELECT id FROM customers WHERE email = ?");
        if ($check_email) {
            $check_email->bind_param("s", $email);
            $check_email->execute();
            $result = $check_email->get_result();

            if ($result->num_rows > 0) {
                $_SESSION['toast'] = [
                    'type' => 'error',
                    'message' => "Email already registered"
                ];
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new customer
                $stmt = $conn->prepare("INSERT INTO customers (username, nic, email, password, dob, phone_personal, phone_work, address, postal_code, country, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt) {
                    $stmt->bind_param("ssssssssssss", $username, $nic, $email, $hashed_password, $dob, $phone_personal, $phone_work, $address, $postal_code, $country, $created_at, $updated_at);

                    if ($stmt->execute()) {
                        $_SESSION['toast'] = [
                            'type' => 'success',
                            'message' => "Registration successful! Please login"
                        ];
                        header("Location: login.php");
                        exit();
                    } else {
                        $_SESSION['toast'] = [
                            'type' => 'error',
                            'message' => "Registration failed. Please try again"
                        ];
                    }
                } else {
                    $_SESSION['toast'] = [
                        'type' => 'error',
                        'message' => "Database error. Please try again later"
                    ];
                }
            }
        } else {
            $_SESSION['toast'] = [
                'type' => 'error',
                'message' => "Database error. Please try again later"
            ];
        }
    } else {
        $_SESSION['toast'] = [
            'type' => 'error',
            'message' => implode("<br>", $errors)
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CeylonCart</title>
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


        /* White box */
        .container {
            position: relative;
            width: 100%;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center; 
            padding: 40px 16px; 
            overflow: hidden;
        }

        .background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0.8;
            z-index: -1;
        }

        .content {
            background-color: white;
            border-radius: 24px;
            padding: 32px;
            max-width: 448px;
            width: 100%;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin: auto;
        }

        .logo-container {
            margin-bottom: 24px;
        }

        .logo {
            width: 120px;
            height: 85px;
        }

        .title {
            font-size: 32px;
            margin-bottom: 16px;
            color: #2b2b2b;
        }

        .subtitle {
            font-size: 24px;
            margin-bottom: 16px;
            color: #333;
        }

        .form-group {
            margin-bottom: 16px;
            text-align: left;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 4px;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            background-color: #f3f4f6;
            border-radius: 8px;
            border: none;
            font-family: sans-serif;
            box-sizing: border-box;
            height: 48px;
            font-size: 16px;
        }

        /* Form Row for EMAIL/Password, Personal ID/DOB, and Postal Code/Country */
        .form-row {
            display: flex;
            gap: 16px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 16px;
        }

        .button {
            background-color: #F35821;
            color: white;
            border: none;
            border-radius: 16px;
            padding: 6px 24px;
            font-size: 24px;
            font-family: 'Koulen', sans-serif;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .button:hover {
            background-color: #e04e1b;
        }

        .continue-section {
            margin-top: 24px;
        }

        .continue {
            width: 100%;
            margin-bottom: 16px;
        }

        .login-text {
            font-size: 14px;
            color: #666;
        }

        .login-link {
            color: #F35821;
            text-decoration: none;
            font-weight: bold;
        }

        .login-link:hover {
            text-decoration: underline;
        }

        /* Add these styles to your existing CSS */
        .validation-message {
            color: #dc2626;
            font-size: 12px;
            margin-top: 4px;
            min-height: 16px;
            font-family: sans-serif;
        }

        .form-input.error {
            border: 1px solid #dc2626;
        }

        .form-input.valid {
            border: 1px solid #059669;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="background"></div>

        <div class="content">
        <div class="logo-container">
            <img src="Images/LogoL.png" alt="Logo" class="logo">
        </div>

        <form class="sign-in-form" action="register.php" method="POST">
            <h1 class="title">SIGN IN</h1>

            <div class="user-details">
            <div class="form-group">
                <label for="username" class="form-label">USER NAME</label>
                <input type="text" id="username" name="username" class="form-input" placeholder="Joe Miller" required>
                <div class="validation-message" id="username-error"></div>
            </div>

            <div class="form-row">
                <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="someone@gmail.com" required>
                <div class="validation-message" id="email-error"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                <label for="nic" class="form-label">PERSONAL ID NUMBER</label>
                <input type="text" id="nic" name="nic" class="form-input" placeholder="2000XXXXX4234" required>
                <div class="validation-message" id="nic-error"></div>
                </div>
                <div class="form-group">
                <label for="dob" class="form-label">DATE OF BIRTH</label>
                <input type="date" id="dob" name="dob" class="form-input" required>
                <div class="validation-message" id="dob-error"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                <label for="phone-personal" class="form-label">PHONE 1</label>
                <input type="text" id="phone-personal" name="phone-personal" class="form-input" placeholder="Personal" required>
                <div class="validation-message" id="phone-personal-error"></div>
                </div>
                <div class="form-group">
                <label for="phone-2" class="form-label">PHONE 2</label>
                <input type="text" id="phone-2" name="phone-work" class="form-input" placeholder="Home/Work" required>
                <div class="validation-message" id="phone-work-error"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="address" class="form-label">ADDRESS</label>
                <input type="text" id="address" name="address" class="form-input" placeholder="No. 888/8, Pine Avenue, Springfield" required>
                <div class="validation-message" id="address-error"></div>
            </div>

            <div class="form-row">
                <div class="form-group">
                <label for="postal-code" class="form-label">POSTAL CODE</label>
                <input type="text" id="postal-code" name="postal-code" class="form-input" placeholder="80000" required>
                <div class="validation-message" id="postal-code-error"></div>
                </div>
                <div class="form-group">
                <label for="country" class="form-label">COUNTRY</label>
                <input type="text" id="country" name="country" class="form-input" placeholder="Sri Lanka">
                <div class="validation-message" id="country-error"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password" class="form-label">PASSWORD</label>
                    <input type="password" id="password" name="password" placeholder="••••••••••" class="form-input" required>
                    <div class="validation-message" id="password-error"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">CONFIRM PASSWORD</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••••" class="form-input" required>
                    <div class="validation-message" id="confirm-password-error"></div>
                </div>
            </div>

            <div class="continue-section">
                <button type="submit" class="button continue">Create Account</button>
            <p class="login-text">ALREADY HAVE AN ACCOUNT? <a href="login.php" class="login-link">LOG IN</a></p>
            </div>
        </form>
        </div>
    </div>








<!--
    <div class="register-page">
        <div class="register-container">
            <button class="back-button">
                ← Back
            </button>

            <div class="content">
            <div class="logo-container">
                <img src="Images/LogoL.png" alt="Logo" class="logo">
            </div>

            <h1 class="title">SIGN UP</h1>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">FULL NAME</label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" class="input-field" required>
                </div>

                <div class="form-group">
                    <label for="email">EMAIL</label>
                    <input type="email" id="email" name="email" placeholder="someone@gmail.com" class="input-field" required>
                </div>

                <div class="form-group">
                    <label for="phone">PHONE NUMBER</label>
                    <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" class="input-field" required>
                </div>

                <div class="form-group">
                    <label for="address">ADDRESS</label>
                    <textarea id="address" name="address" placeholder="Enter your address" class="input-field" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label for="postal_code">POSTAL CODE</label>
                    <input type="text" id="postal_code" name="postal_code" placeholder="Enter your postal code" class="input-field" required>
                </div>

                <div class="form-group password-group">
                    <label for="password">PASSWORD</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" placeholder="••••••••••" class="input-field" required>
                        <i class="fas fa-eye eye-icon"></i>
                    </div>
                </div>

                <div class="form-group password-group">
                    <label for="confirm_password">CONFIRM PASSWORD</label>
                    <div class="password-container">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••••" class="input-field" required>
                        <i class="fas fa-eye eye-icon"></i>
                    </div>
                </div>

                <button type="submit" class="register-button">SIGN UP</button>
            </form>

            <p class="login-link">ALREADY HAVE AN ACCOUNT? 
                <a href="login.php" class="orange-link">LOG IN</a>
            </p>
        </div>
    </div>
-->   

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        $(document).ready(function() {
            // Toggle password visibility
            $('.eye-icon').click(function() {
                const passwordInput = $(this).siblings('input');
                const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
                passwordInput.attr('type', type);
                $(this).toggleClass('fa-eye fa-eye-slash');
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
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('.sign-in-form');
        const dobInput = document.getElementById('dob');
        const nicInput = document.getElementById('nic');
        const phonePersonalInput = document.getElementById('phone-personal');
        const phoneWorkInput = document.getElementById('phone-2');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const emailInput = document.getElementById('email');
        const usernameInput = document.getElementById('username');

        // Function to show validation message
        function showValidationMessage(elementId, message, isValid) {
            const errorElement = document.getElementById(elementId + '-error');
            const inputElement = document.getElementById(elementId);
            
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.color = isValid ? '#059669' : '#dc2626';
            }
            
            if (inputElement) {
                inputElement.classList.remove('error', 'valid');
                if (message) {
                    inputElement.classList.add(isValid ? 'valid' : 'error');
                }
            }
        }

        // Set max date for DOB (December 31, 2019)
        const maxDate = new Date('2019-12-31');
        dobInput.max = maxDate.toISOString().split('T')[0];

        // Username validation
        usernameInput.addEventListener('input', function() {
            const value = this.value.trim();
            if (value.length < 3) {
                showValidationMessage('username', 'Username must be at least 3 characters long', false);
            } else if (!/^[A-Za-z\s]+$/.test(value)) {
                showValidationMessage('username', 'Username can only contain letters and spaces', false);
            } else {
                showValidationMessage('username', 'Username is valid', true);
            }
        });

        // NIC validation
        nicInput.addEventListener('input', function() {
            const value = this.value.replace(/[^0-9]/g, '').slice(0, 12);
            this.value = value;
            
            if (value.length !== 12) {
                showValidationMessage('nic', 'NIC must be exactly 12 digits', false);
            } else {
                showValidationMessage('nic', 'NIC is valid', true);
            }
        });

        // DOB validation
        dobInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const maxDate = new Date('2019-12-31');
            
            if (selectedDate > maxDate) {
                showValidationMessage('dob', 'Date of birth must be before 2020', false);
            } else {
                showValidationMessage('dob', 'Date of birth is valid', true);
            }
        });

        // Phone validation
        function validatePhone(input, elementId) {
            input.addEventListener('input', function() {
                const value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
                this.value = value;
                
                if (value.length !== 10) {
                    showValidationMessage(elementId, 'Phone number must be exactly 10 digits', false);
                } else {
                    showValidationMessage(elementId, 'Phone number is valid', true);
                }
            });
        }
        validatePhone(phonePersonalInput, 'phone-personal');
        validatePhone(phoneWorkInput, 'phone-2');

        // Email validation
        emailInput.addEventListener('input', function() {
            const email = this.value;
            const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            showValidationMessage('email', isValid ? 'Email is valid' : 'Please enter a valid email address', isValid);
        });

        // Password validation
        function validatePassword(password) {
            const minLength = 8;
            const hasUpperCase = /[A-Z]/.test(password);
            const hasLowerCase = /[a-z]/.test(password);
            const hasNumbers = /\d/.test(password);
            const hasSpecialChar = /[!@#$%^&*()\-_=+{};:,<.>]/.test(password);

            const errors = [];
            if (password.length < minLength) errors.push('At least 8 characters');
            if (!hasUpperCase) errors.push('One uppercase letter');
            if (!hasLowerCase) errors.push('One lowercase letter');
            if (!hasNumbers) errors.push('One number');
            if (!hasSpecialChar) errors.push('One special character');

            return {
                isValid: errors.length === 0,
                message: errors.length > 0 ? 'Password must contain: ' + errors.join(', ') : 'Password is valid'
            };
        }

        passwordInput.addEventListener('input', function() {
            const validation = validatePassword(this.value);
            showValidationMessage('password', validation.message, validation.isValid);
            
            // Update confirm password validation
            if (confirmPasswordInput.value) {
                const isMatch = this.value === confirmPasswordInput.value;
                showValidationMessage('confirm-password', 
                    isMatch ? 'Passwords match' : 'Passwords do not match', 
                    isMatch
                );
            }
        });

        // Confirm password validation
        confirmPasswordInput.addEventListener('input', function() {
            const isMatch = this.value === passwordInput.value;
            showValidationMessage('confirm-password', 
                isMatch ? 'Passwords match' : 'Passwords do not match', 
                isMatch
            );
        });

        // Form submission validation
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const inputs = form.querySelectorAll('input[required]');
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    showValidationMessage(input.id, 'This field is required', false);
                    isValid = false;
                }
            });

            if (!isValid) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html> 