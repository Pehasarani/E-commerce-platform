<?php
session_start();
include('connection.php');

// Check if supplier is logged in
if (!isset($_SESSION['supplier_id'])) {
    header("Location: supplierLogin.php");
    exit();
}

$supplier_id = $_SESSION['supplier_id'];
$message = '';
$error = '';
$supplier = null;

// Fetch supplier details
try {
    $stmt = $conn->prepare("SELECT `supplier_id`, `business_name`, `business_email`, `password`, `business_address`, `contact_number`, `created_at`, `updated_at` FROM `suppliers` WHERE supplier_id = $supplier_id");
    if (!$stmt) {
        throw new Exception("Error preparing supplier query: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $supplier = $result->fetch_assoc();
    $stmt->close();

} catch (Exception $e) {
    error_log("Profile Error: " . $e->getMessage());
    $error = "Error fetching supplier details";
}

// At the top of your PHP code, initialize error array
$fieldErrors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_name = $_POST['business_name'];
    $business_email = $_POST['business_email'];
    $contact_number = $_POST['contact_number'];
    $business_address = $_POST['business_address'];
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate business name
    if (empty($business_name)) {
        $fieldErrors['business_name'] = "Business name is required";
    } elseif (!preg_match("/^[A-Za-z\s]+$/", $business_name)) {
        $fieldErrors['business_name'] = "Only letters and spaces are allowed";
    } elseif (strlen($business_name) < 3) {
        $fieldErrors['business_name'] = "Business name must be at least 3 characters";
    }

    // Validate contact number
    if (empty($contact_number)) {
        $fieldErrors['contact_number'] = "Contact number is required";
    } elseif (!preg_match("/^\d{10}$/", $contact_number)) {
        $fieldErrors['contact_number'] = "Contact number must be exactly 10 digits";
    }

    // Validate business address
    if (empty($business_address)) {
        $fieldErrors['business_address'] = "Business address is required";
    }

    // Password validation
    if (!empty($current_password)) {
        // Fetch current password from database
        $stmt = $conn->prepare("SELECT password FROM suppliers WHERE supplier_id = ?");
        $stmt->bind_param("i", $supplier_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_pwd_row = $result->fetch_assoc();
        $stmt->close();

        // Remove the debug echo
        // echo $current_pwd_row['password'];
        echo $current_pwd_row['password']==$current_password;

        // Fix the password verification condition
        if (!password_verify($current_password, $current_pwd_row['password']) && $current_password != $current_pwd_row['password']) {
            $fieldErrors['current_password'] = "Current password is incorrect";
        } elseif (empty($new_password)) {
            $fieldErrors['new_password'] = "New password is required";
        } elseif (strlen($new_password) < 8) {
            $fieldErrors['new_password'] = "Password must be at least 8 characters";
        } elseif ($new_password !== $confirm_password) {
            $fieldErrors['confirm_password'] = "Passwords do not match";
        }
    }

    // Only proceed if there are no errors
    if (empty($fieldErrors)) {
        try {
            // Start with basic profile update
            $stmt = $conn->prepare("UPDATE suppliers SET 
                business_name = ?,
                contact_number = ?,
                business_address = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE supplier_id = ?");
            
            $stmt->bind_param("sssi", 
                $business_name,
                $contact_number,
                $business_address,
                $supplier_id
            );
            
            // Execute the basic update
            if ($stmt->execute()) {
                // If there's a password change request
                if (!empty($current_password) && !empty($new_password)) {
                    // Hash the new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Prepare password update statement
                    $pwd_stmt = $conn->prepare("UPDATE suppliers SET 
                        password = ?
                        WHERE supplier_id = ?");
                    
                    $pwd_stmt->bind_param("si", $hashed_password, $supplier_id);
                    
                    if ($pwd_stmt->execute()) {
                        $message = "Profile and password updated successfully!";
                    } else {
                        $error = "Profile updated but password update failed.";
                    }
                    $pwd_stmt->close();
                } else {
                    $message = "Profile updated successfully!";
                }
            } else {
                $error = "Error updating profile.";
            }
            $stmt->close();
            
            // Refresh supplier data after update
            $refresh_stmt = $conn->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
            $refresh_stmt->bind_param("i", $supplier_id);
            $refresh_stmt->execute();
            $result = $refresh_stmt->get_result();
            $supplier = $result->fetch_assoc();
            $refresh_stmt->close();
            
        } catch (Exception $e) {
            error_log("Update Error: " . $e->getMessage());
            $error = "Error updating profile: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Profile</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .logo-container { display: flex; align-items: center; }
        .logo { width: 120px; height: auto; }
        .navbar { background: #222; color: #fff; display: flex; align-items: center; justify-content: space-between; padding: 12px 32px; }
        .navbar-text { font-size: 1.3rem; font-weight: bold; letter-spacing: 1px; }
        .navbar-profile { display: flex; align-items: center; gap: 10px; font-size: 1.1rem; }
        .profile-icon { font-size: 1.7rem; }
    </style>
</head>
<body class="dashboard-body">
    <nav class="navbar">
        <div class="logo-container">
            <img src="./images/suplogo22.png" alt="CeylonCart Logo" class="logo">
        </div>
        <span class="navbar-text">Supplier Center</span>
        <div class="navbar-profile">
            <i class="fas fa-user-circle profile-icon"></i>
            <span><?php echo htmlspecialchars($_SESSION['business_name'] ?? 'Supplier'); ?></span>
        </div>
    </nav>

    <div class="dashboard-container">
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="supplierDashboard.php" style="text-decoration: none; color: white;">
                        <i class="fas fa-tachometer-alt sidebar-icon"></i>
                        <span class="sidebar-text">Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="supplierAddProduct.php" style="text-decoration: none; color: white;">
                        <i class="fas fa-plus-circle sidebar-icon"></i>
                        <span class="sidebar-text">Add Product</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="supplierNotification.php" style="text-decoration: none; color: white;">
                        <i class="fas fa-bell sidebar-icon"></i>
                        <span class="sidebar-text">Order Notification</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="supplierPayment.php" style="text-decoration: none; color: white;">
                        <i class="fas fa-credit-card sidebar-icon"></i>
                        <span class="sidebar-text">Payment</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="supplierProfile.php" style="text-decoration: none; color: white;">
                        <i class="fas fa-user sidebar-icon"></i>
                        <span class="sidebar-text">Profile</span>
                    </a>
                </li>
            </ul>
        </aside>

        <main class="main-content p-6">
            <div class="header-container">
                <img src="./images/spicy.png" alt="Spice Hut Logo" class="header-logo">
                <h1>Profile Settings</h1>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                <?php if ($message): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="profileForm" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="business_name">
                                Business Name *
                            </label>
                            <input type="text" 
                                   id="business_name" 
                                   name="business_name" 
                                   required
                                   class="shadow appearance-none border <?php echo isset($fieldErrors['business_name']) ? 'border-red-500' : ''; ?> rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white"
                                   value="<?php echo htmlspecialchars($supplier['business_name'] ?? ''); ?>">
                            <?php if (isset($fieldErrors['business_name'])): ?>
                                <p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($fieldErrors['business_name']); ?></p>
                            <?php endif; ?>
                            <span class="text-xs text-gray-500">Only letters (A-Z, a-z) and spaces allowed</span>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="business_email">
                                Business Email *
                            </label>
                            <input type="email" 
                                   id="business_email" 
                                   name="business_email" 
                                   readonly
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-100"
                                   value="<?php echo htmlspecialchars($supplier['business_email'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="contact_number">
                                Contact Number *
                            </label>
                            <input type="tel" 
                                   id="contact_number" 
                                   name="contact_number" 
                                   required
                                   maxlength="10"
                                   class="shadow appearance-none border <?php echo isset($fieldErrors['contact_number']) ? 'border-red-500' : ''; ?> rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white"
                                   value="<?php echo htmlspecialchars($supplier['contact_number'] ?? ''); ?>">
                            <?php if (isset($fieldErrors['contact_number'])): ?>
                                <p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($fieldErrors['contact_number']); ?></p>
                            <?php endif; ?>
                            <span class="text-xs text-gray-500">Exactly 10 digits required</span>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="business_address">
                                Business Address *
                            </label>
                            <textarea id="business_address" 
                                      name="business_address" 
                                      required
                                      class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white"
                                      rows="3"><?php echo htmlspecialchars($supplier['business_address'] ?? ''); ?></textarea>
                            <div id="addressError" class="text-red-500 text-sm mt-1 hidden"></div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 my-4 pt-4">
                        <h3 class="text-lg font-semibold mb-4">Change Password</h3>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="current_password">
                                    Current Password
                                </label>
                                <div class="relative">
                                    <input type="password" 
                                           id="current_password" 
                                           name="current_password"
                                           class="shadow appearance-none border <?php echo isset($fieldErrors['current_password']) ? 'border-red-500' : ''; ?> rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white pr-10">
                                    <span class="absolute right-2 top-2 cursor-pointer">
                                        <i class="fas fa-eye toggle-password" data-target="current_password"></i>
                                    </span>
                                </div>
                                <?php if (isset($fieldErrors['current_password'])): ?>
                                    <p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($fieldErrors['current_password']); ?></p>
                                <?php endif; ?>
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="new_password">
                                    New Password
                                </label>
                                <div class="relative">
                                    <input type="password" 
                                           id="new_password" 
                                           name="new_password"
                                           class="shadow appearance-none border <?php echo isset($fieldErrors['new_password']) ? 'border-red-500' : ''; ?> rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white pr-10">
                                    <span class="absolute right-2 top-2 cursor-pointer">
                                        <i class="fas fa-eye toggle-password" data-target="new_password"></i>
                                    </span>
                                </div>
                                <?php if (isset($fieldErrors['new_password'])): ?>
                                    <p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($fieldErrors['new_password']); ?></p>
                                <?php endif; ?>
                                <span class="text-xs text-gray-500">Minimum 8 characters required</span>
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">
                                    Confirm New Password
                                </label>
                                <div class="relative">
                                    <input type="password" 
                                           id="confirm_password" 
                                           name="confirm_password"
                                           class="shadow appearance-none border <?php echo isset($fieldErrors['confirm_password']) ? 'border-red-500' : ''; ?> rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white pr-10">
                                    <span class="absolute right-2 top-2 cursor-pointer">
                                        <i class="fas fa-eye toggle-password" data-target="confirm_password"></i>
                                    </span>
                                </div>
                                <?php if (isset($fieldErrors['confirm_password'])): ?>
                                    <p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($fieldErrors['confirm_password']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-4 mt-6">
                        <button type="submit" 
                                class="bg-[#FF6F00] hover:bg-[#ff4b00] text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('profileForm');
    const currentPassword = document.getElementById('current_password');
    const newPassword = document.getElementById('new_password');
    const businessNameInput = document.getElementById('business_name');
    const contactNumberInput = document.getElementById('contact_number');

    // Validation functions
    const validators = {
        business_name: (value) => {
            const regex = /^[A-Za-z\s]+$/;
            if (!value.trim()) {
                return "Business name is required";
            }
            if (!regex.test(value)) {
                return "Only letters (A-Z, a-z) and spaces are allowed";
            }
            if (value.length < 3) {
                return "Business name must be at least 3 characters long";
            }
            return "";
        },
        contact_number: (value) => {
            const regex = /^\d{10}$/;
            if (!value.trim()) {
                return "Contact number is required";
            }
            if (!regex.test(value)) {
                return "Contact number must be exactly 10 digits";
            }
            return "";
        }
    };

    // Show/hide error message
    function showError(element, errorDiv, message) {
        if (message) {
            errorDiv.textContent = message;
            element.classList.add('border-red-500');
        } else {
            errorDiv.textContent = '';
            element.classList.remove('border-red-500');
        }
    }

    // Business name validation
    businessNameInput.addEventListener('input', function(e) {
        const value = e.target.value;
        // Remove any characters that aren't letters or spaces
        const sanitizedValue = value.replace(/[^A-Za-z\s]/g, '');
        if (value !== sanitizedValue) {
            this.value = sanitizedValue;
        }
        
        const error = validators.business_name(this.value);
        showError(this, document.getElementById('businessNameError'), error);
    });

    // Contact number validation
    contactNumberInput.addEventListener('input', function(e) {
        const value = e.target.value;
        // Remove any non-digit characters
        const sanitizedValue = value.replace(/\D/g, '').slice(0, 10);
        if (value !== sanitizedValue) {
            this.value = sanitizedValue;
        }
        
        const error = validators.contact_number(this.value);
        showError(this, document.getElementById('contactNumberError'), error);
    });

    // Handle current password input
    currentPassword.addEventListener('input', function() {
        if (this.value.trim()) {
            newPassword.removeAttribute('disabled');
            newPassword.classList.remove('bg-gray-100');
        } else {
            newPassword.setAttribute('disabled', true);
            newPassword.classList.add('bg-gray-100');
            newPassword.value = ''; // Clear new password when current password is empty
        }
    });

    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(icon => {
        icon.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (input.type === 'password') {
                input.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    });

    // Form submission
    form.addEventListener('submit', function(e) {
        let hasErrors = false;

        // Validate business name
        const businessNameError = validators.business_name(businessNameInput.value);
        showError(businessNameInput, document.getElementById('businessNameError'), businessNameError);
        if (businessNameError) hasErrors = true;

        // Validate contact number
        const contactNumberError = validators.contact_number(contactNumberInput.value);
        showError(contactNumberInput, document.getElementById('contactNumberError'), contactNumberError);
        if (contactNumberError) hasErrors = true;

        // Validate password change
        if (newPassword.value && !currentPassword.value) {
            showError(currentPassword, document.getElementById('currentPasswordError'), 
                     "Current password is required to change password");
            hasErrors = true;
        }

        if (hasErrors) {
            e.preventDefault();
        }
    });
});
</script>

<style>
.border-red-500 {
    border-color: #f56565 !important;
}

.text-red-500 {
    color: #f56565;
}

input:disabled {
    background-color: #f3f4f6;
    cursor: not-allowed;
}

.toggle-password:hover {
    color: #FF6F00;
}

input:focus:not(.border-red-500), textarea:focus:not(.border-red-500) {
    border-color: #FF6F00;
    box-shadow: 0 0 8px rgba(255, 111, 0, 0.2);
}
</style>






