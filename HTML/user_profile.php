<?php
session_start();
include('connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Fetch user data
$user_query = "SELECT * FROM customers WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if (!$user_result) {
    die("Error fetching user data: " . $conn->error);
}

$user = $user_result->fetch_assoc();

if (!$user) {
    die("User not found");
}

// Initialize default values for user data
$user = array_merge([
    'username' => '',
    'phone_personal' => '',
    'phone_work' => '',
    'address' => '',
    'postal_code' => '',
    'country' => '',
    'email' => ''
], $user);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username']);
    $phone_personal = trim($_POST['phone_personal']);
    $phone_work = trim($_POST['phone_work'] ?? '');
    $address = trim($_POST['address']);
    $postal_code = trim($_POST['postal_code']);
    $country = trim($_POST['country']);
    
    // Validate form data
    $fieldErrors = [];
    
    // Validate username (only letters and spaces)
    if (empty($username)) {
        $fieldErrors['username'] = "Username is required";
    } elseif (!preg_match("/^[A-Za-z\s]+$/", $username)) {
        $fieldErrors['username'] = "Only letters and spaces are allowed";
    } elseif (strlen($username) < 3) {
        $fieldErrors['username'] = "Username must be at least 3 characters";
    }

    // Validate contact number
    if (empty($phone_personal)) {
        $fieldErrors['phone_personal'] = "Contact number is required";
    } elseif (!preg_match("/^\d{10}$/", $phone_personal)) {
        $fieldErrors['phone_personal'] = "Contact number must be exactly 10 digits";
    }
    
    // Validate work phone (optional)
    if (!empty($phone_work) && !preg_match("/^\d{10}$/", $phone_work)) {
        $fieldErrors['phone_work'] = "Work phone must be exactly 10 digits";
    }

    // Validate address
    if (empty($address)) {
        $fieldErrors['address'] = "Address is required";
    }
    
    // Validate postal code
    if (empty($postal_code)) {
        $fieldErrors['postal_code'] = "Postal code is required";
    }
    
    // Validate country
    if (empty($country)) {
        $fieldErrors['country'] = "Country is required";
    }
    
    // If no errors, update the profile
    if (empty($fieldErrors)) {
        try {
            // Prepare the update query with parameterized statement
            $update_query = "UPDATE customers SET 
                            username = ?,
                            phone_personal = ?,
                            phone_work = ?,
                            address = ?,
                            postal_code = ?,
                            country = ?,
                            updated_at = NOW()
                            WHERE id = ?";
            
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssssssi", 
                $username, 
                $phone_personal, 
                $phone_work, 
                $address, 
                $postal_code, 
                $country, 
                $user_id
            );
            
            if ($stmt->execute()) {
                $success_message = "Profile updated successfully!";
                
                // Refresh user data
                $stmt = $conn->prepare($user_query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user_result = $stmt->get_result();
                $user = $user_result->fetch_assoc();
            } else {
                $error_message = "Error updating profile: " . $stmt->error;
            }
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    } else {
        $error_message = "Please correct the errors in the form";
    }
}

// Add this at the top of the file after session_start()
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_profile'])) {
    try {
        // Start transaction
        $conn->begin_transaction();

        // First delete related records from saved_card table
        $delete_saved_card = "DELETE FROM saved_card WHERE user_id = ?";
        $stmt = $conn->prepare($delete_saved_card);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Then delete the user's data from customers table
        $delete_query = "DELETE FROM customers WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            // Commit transaction
            $conn->commit();
            // Clear session and redirect to login page
            session_destroy();
            header("Location: login.php?message=profile_deleted");
            exit();
        } else {
            // Rollback transaction on error
            $conn->rollback();
            $error_message = "Error deleting profile: " . $stmt->error;
        }
    } catch (Exception $e) {
        // Rollback transaction on exception
        $conn->rollback();
        $error_message = "Error deleting profile: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Details - CeylonCart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&family=Abel&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- Add Toastify CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <style>
        .body {
            margin: 0;
            font-family: 'Abel', sans-serif;
            background-color: #f0f0f0;
            color: #333;
        }

        /* Header */
        .header {
            background-color: #F35821;
            position: sticky;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Logo on the Left */
        .logo-container {
            display: flex;
            align-items: center;
        }

        .logo {
            width: 200px;
            height: auto;
        }

        /* Search Bar in the Middle */
        .search-bar {
            flex: 1;
            margin: 0 24px;
            max-width: 500px; 
        }

        .search-input {
            width: 100%;
            padding: 8px 16px;
            border: none;
            border-radius: 16px;
            font-family: 'Abel', sans-serif;
            font-size: 16px;
            outline: none;
        }

        /* Buttons on the Right */
        .header-buttons {
            display: flex;
            gap: 16px;
            align-items: center;
            margin-right: 24px;
        }

        /* Profile Button */
        .profile-button {
            background-color: #2B2B2B;
            border: none;
            border-radius: 16px; 
            padding: 8px 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px; 
        }

        .profile-icon {
            width: 24px;
            height: 24px;
        }

        /* Cart Button */
        .cart-button {
            background-color: #FFFFFF;
            border: none;
            border-radius: 16px;
            padding: 8px 16px; 
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px; 
        }

        .cart-icon {
            width: 24px;
            height: 24px;
        }

        /* Dropdown Menu */
        .profile-dropdown {
            position: relative;
            display: inline-block;
            margin-right: 10px;
        }

        .page-title {
            font-size: 22px;
            color: #2B2B2B; 
            margin-bottom: 24px;
            font-family: 'Koulen', sans-serif;
            text-align: left; 
            padding-left: 16px; 
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #2B2B2B; 
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1;
            border-radius: 16px; 
            overflow: hidden; 
        }

        .dropdown-content a {
            color: #ffffff; 
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-family: 'Koulen', sans-serif; 
            font-size: 16px;
        }

        .dropdown-content a:hover {
            background-color: #F35821; 
        }

        .profile-dropdown:hover .dropdown-content {
            display: block;
        }

        .profile-details p {
            margin: 10px;
        }
        .social-icons img {
            height: 30px;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .flatpickr-calendar {
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border-radius: 0.5rem;
        }
        .flatpickr-day.selected {
            background: #ff6f00;
            border-color: #ff6f00;
        }
        .flatpickr-day.today {
            border-color: #ff6f00;
        }
        .flatpickr-day:hover {
            background: #ff6f00;
            border-color: #ff6f00;
        }
        .toastify {
            background: linear-gradient(to right, #ff6f00, #ff8f00);
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .toastify-error {
            background: linear-gradient(to right, #ff4444, #ff6b6b);
        }
        
        /* Debug Panel Styles */
        .debug-panel {
            position: fixed;
            bottom: 0;
            right: 0;
            width: 400px;
            height: 300px;
            background: rgba(0, 0, 0, 0.9);
            color: #fff;
            padding: 15px;
            font-family: monospace;
            font-size: 12px;
            overflow-y: auto;
            border-left: 2px solid #ff6f00;
            display: none;
            z-index: 1000;
        }

        .user-details-title {
            font-family: 'Koulen', sans-serif;
            font-size: 28px;
            color: #2B2B2B;
            margin-bottom: 15px;
            text-align: left;
        }
        
        .debug-panel.active {
            display: block;
        }
        
        .debug-toggle {
            position: fixed;
            bottom: 0;
            right: 0;
            background: #ff6f00;
            color: white;
            padding: 5px 10px;
            cursor: pointer;
            z-index: 1001;
        }
        
        .debug-log {
            margin: 5px 0;
            padding: 5px;
            border-bottom: 1px solid #333;
        }
        
        .debug-log.error {
            color: #ff6b6b;
        }
        
        .debug-log.success {
            color: #4ade80;
        }
        
        .debug-log.info {
            color: #60a5fa;
        }
    </style>
</head>
<body class="font-sans m-0 p-0 bg-gray-50">
    <header class="header">

        <a href = "product_view.php">
            <div class="logo-container">
                <img src="Images/LogoVertical.png" alt="CeylonCart Logo" class="logo">
            </div>
        </a>

        <div class="search-bar">
                <input type="text" id="searchFilter" placeholder="Search products..." class="search-input">
        </div>
            
        <!-- Buttons on the Right -->
        <div class="header-buttons">
        <!-- View Cart Button -->
        <a href="cart.php">
        <button class="cart-button">
        <img src="Images/ViewCart.png" alt="View Cart" class="cart-icon">
        </button>
        </a>


            <div class="profile-dropdown">
                <button class="profile-button">
                    <img src="Images/profile-picture.png" alt="Profile" class="profile-icon">
                </button>
                <div class="dropdown-content">
                    <a href="user_profile.php">VIEW PROFILE</a>
                    <a href="orderhistory.php">ORDER HISTORY</a>
                    <a href="login.php">LOG OUT</a>
                </div>
            </div>
        </div>
    </header>
    
    <main class="p-5 max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h1 class="user-details-title">Profile Details</h1>
            
            <?php if (!empty($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $success_message; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $error_message; ?></span>
                </div>
            <?php endif; ?>
            
            <form id="profile-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#ff6f00] focus:ring focus:ring-[#ff6f00] focus:ring-opacity-50 <?php echo isset($fieldErrors['username']) ? 'border-red-500' : ''; ?>">
                        <?php if (isset($fieldErrors['username'])): ?>
                            <p class="text-red-500 text-xs mt-1"><?php echo $fieldErrors['username']; ?></p>
                        <?php endif; ?>
                        <p id="usernameError" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required readonly
                               class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm focus:border-[#ff6f00] focus:ring focus:ring-[#ff6f00] focus:ring-opacity-50 cursor-not-allowed">
                        <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                    </div>
                    
                    <div>
                        <label for="phone_personal" class="block text-sm font-medium text-gray-700">Personal Phone</label>
                        <input type="tel" id="phone_personal" name="phone_personal" value="<?php echo htmlspecialchars($user['phone_personal']); ?>" required
                               maxlength="10" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#ff6f00] focus:ring focus:ring-[#ff6f00] focus:ring-opacity-50 <?php echo isset($fieldErrors['phone_personal']) ? 'border-red-500' : ''; ?>">
                        <?php if (isset($fieldErrors['phone_personal'])): ?>
                            <p class="text-red-500 text-xs mt-1"><?php echo $fieldErrors['phone_personal']; ?></p>
                        <?php endif; ?>
                        <p id="phonePersonalError" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <div>
                        <label for="phone_work" class="block text-sm font-medium text-gray-700">Work Phone (Optional)</label>
                        <input type="tel" id="phone_work" name="phone_work" value="<?php echo htmlspecialchars($user['phone_work']); ?>"
                               maxlength="10"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#ff6f00] focus:ring focus:ring-[#ff6f00] focus:ring-opacity-50 <?php echo isset($fieldErrors['phone_work']) ? 'border-red-500' : ''; ?>">
                        <?php if (isset($fieldErrors['phone_work'])): ?>
                            <p class="text-red-500 text-xs mt-1"><?php echo $fieldErrors['phone_work']; ?></p>
                        <?php endif; ?>
                        <p id="phoneWorkError" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea id="address" name="address" required
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#ff6f00] focus:ring focus:ring-[#ff6f00] focus:ring-opacity-50 <?php echo isset($fieldErrors['address']) ? 'border-red-500' : ''; ?>"><?php echo htmlspecialchars($user['address']); ?></textarea>
                        <?php if (isset($fieldErrors['address'])): ?>
                            <p class="text-red-500 text-xs mt-1"><?php echo $fieldErrors['address']; ?></p>
                        <?php endif; ?>
                        <p id="addressError" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <div>
                        <label for="postal_code" class="block text-sm font-medium text-gray-700">Postal Code</label>
                        <input type="text" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($user['postal_code']); ?>" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#ff6f00] focus:ring focus:ring-[#ff6f00] focus:ring-opacity-50 <?php echo isset($fieldErrors['postal_code']) ? 'border-red-500' : ''; ?>">
                        <?php if (isset($fieldErrors['postal_code'])): ?>
                            <p class="text-red-500 text-xs mt-1"><?php echo $fieldErrors['postal_code']; ?></p>
                        <?php endif; ?>
                        <p id="postalCodeError" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700">Country</label>
                        <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($user['country']); ?>" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#ff6f00] focus:ring focus:ring-[#ff6f00] focus:ring-opacity-50 <?php echo isset($fieldErrors['country']) ? 'border-red-500' : ''; ?>">
                        <?php if (isset($fieldErrors['country'])): ?>
                            <p class="text-red-500 text-xs mt-1"><?php echo $fieldErrors['country']; ?></p>
                        <?php endif; ?>
                        <p id="countryError" class="text-red-500 text-xs mt-1"></p>
                    </div>
                </div>


                <div class="flex justify-end mt-6 space-x-4">
                    <button type="submit" class="bg-[#ff6f00] text-white px-4 py-2 rounded-lg hover:bg-[#e65c00] transition-colors duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 flex items-center" style="font-family: 'Koulen', sans-serif;">
                        Update Profile
                    </button>
                        
                    <a href="download_profile.php" 
                       class="bg-[#2B2B2B] text-white px-4 py-2 rounded-lg hover:bg-[#e65c00] transition-colors duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 flex items-center" style="font-family: 'Koulen', sans-serif;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download PDF
                    </a>

                    <button type="button" onclick="confirmDelete()" 
                            class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 flex items-center" style="font-family: 'Koulen', sans-serif;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete Profile
                    </button>
                </div>

                
            </form>
        </div>
    </main>

    <main class="p-5 max-w-4xl mx-auto">

    </main>

    <footer class="bg-white py-4 px-24 flex justify-between items-center">
        <div class="flex gap-4">
            <img src="Images/facebook.png" alt="Facebook" class="w-7 h-7 hover:scale-110 transition-transform duration-300">
            <img src="Images/instagram.png" alt="Instagram" class="w-7 h-7 hover:scale-110 transition-transform duration-300">
            <img src="Images/twitter.png" alt="Twitter" class="w-7 h-7 hover:scale-110 transition-transform duration-300">
        </div>
        <p class="text-gray-600">© CeylonCart 2025</p>
    </footer>



    <script>
        // Initialize date picker
        flatpickr("#dob", {
            dateFormat: "Y-m-d",
            maxDate: "today",
            disableMobile: true,
            theme: "light",
            onChange: function(selectedDates, dateStr, instance) {
                // Update the form field when a date is selected
                document.getElementById('dob').value = dateStr;
            }
        });

        function enableEditing() {
            const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], textarea');
            inputs.forEach(input => {
                if (!input.hasAttribute('data-readonly')) {
                    input.removeAttribute('readonly');
                    input.classList.remove('bg-gray-100');
                    if (input.id === 'dob') {
                        input._flatpickr.open();
                    }
                }
            });
            
            // Enable save button and disable edit button
            document.getElementById('edit-button').disabled = true;
            document.getElementById('save-button').disabled = false;
            document.getElementById('save-button').classList.remove('opacity-50');
        }

        // Disable editing for NIC field
        document.addEventListener('DOMContentLoaded', function() {
            const readonlyFields = document.querySelectorAll('input[value*="' + '<?php echo htmlspecialchars($user['nic']); ?>' + '"]');
            readonlyFields.forEach(field => {
                field.setAttribute('data-readonly', 'true');
            });

            // Add form submission handler
            document.getElementById('profile-form').addEventListener('submit', function(e) {
                // Validate form before submission
                const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], textarea');
                let isValid = true;

                inputs.forEach(input => {
                    if (input.hasAttribute('required') && !input.value.trim()) {
                        isValid = false;
                        input.classList.add('border-red-500');
                    } else {
                        input.classList.remove('border-red-500');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields');
                }
            });
        });

        // Function to show Toastify notification
        function showNotification(message, type = 'success') {
            Toastify({
                text: message,
                duration: 3000,
                gravity: "top",
                position: "right",
                className: type === 'success' ? 'toastify' : 'toastify toastify-error',
                stopOnFocus: true,
            }).showToast();
        }

        // Show notifications for PHP session messages
        <?php if (isset($_SESSION['success'])): ?>
            showNotification('<?php echo addslashes($_SESSION['success']); ?>', 'success');
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            showNotification('<?php echo addslashes($_SESSION['error']); ?>', 'error');
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        // Add form submission handler with Toastify notifications
        document.getElementById('profile-form').addEventListener('submit', function(e) {
            const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], textarea');
            let isValid = true;
            let errorMessage = '';

            inputs.forEach(input => {
                if (input.hasAttribute('required') && !input.value.trim()) {
                    isValid = false;
                    input.classList.add('border-red-500');
                    errorMessage = 'Please fill in all required fields';
                } else {
                    input.classList.remove('border-red-500');
                }
            });

            if (!isValid) {
                e.preventDefault();
                showNotification(errorMessage, 'error');
            }
        });

        // Show success notification after successful update
        <?php if (isset($_SESSION['update_success'])): ?>
            showNotification('<?php echo addslashes($_SESSION['update_success']); ?>', 'success');
            <?php unset($_SESSION['update_success']); ?>
        <?php endif; ?>

        // Debug Log Functions
        function logToDebugPanel(message, type = 'info') {
            const debugLogs = document.getElementById('debugLogs');
            const logEntry = document.createElement('div');
            logEntry.className = `debug-log ${type}`;
            logEntry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            debugLogs.appendChild(logEntry);
            debugLogs.scrollTop = debugLogs.scrollHeight;
        }

        function toggleDebugPanel() {
            const panel = document.getElementById('debugPanel');
            panel.classList.toggle('active');
        }

        function clearDebugLog() {
            document.getElementById('debugLogs').innerHTML = '';
        }

        // Override console.log to also log to debug panel
        const originalConsoleLog = console.log;
        console.log = function() {
            originalConsoleLog.apply(console, arguments);
            logToDebugPanel(Array.from(arguments).join(' '), 'info');
        };

        // Override console.error to also log to debug panel
        const originalConsoleError = console.error;
        console.error = function() {
            originalConsoleError.apply(console, arguments);
            logToDebugPanel(Array.from(arguments).join(' '), 'error');
        };

        // Log PHP debug messages
        <?php if (isset($_SESSION['debug_log'])): ?>
            <?php foreach ($_SESSION['debug_log'] as $log): ?>
                logToDebugPanel('<?php echo addslashes($log); ?>', 'info');
            <?php endforeach; ?>
            <?php unset($_SESSION['debug_log']); ?>
        <?php endif; ?>

        // Log PHP errors
        <?php if (isset($_SESSION['error'])): ?>
            logToDebugPanel('<?php echo addslashes($_SESSION['error']); ?>', 'error');
        <?php endif; ?>

        // Log PHP success messages
        <?php if (isset($_SESSION['success'])): ?>
            logToDebugPanel('<?php echo addslashes($_SESSION['success']); ?>', 'success');
        <?php endif; ?>

        // Add debug logging to form submission
        document.getElementById('profile-form').addEventListener('submit', function(e) {
            const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], textarea');
            let isValid = true;
            let errorMessage = '';

            inputs.forEach(input => {
                if (input.hasAttribute('required') && !input.value.trim()) {
                    isValid = false;
                    input.classList.add('border-red-500');
                    errorMessage = 'Please fill in all required fields';
                    logToDebugPanel(`Validation error: ${input.name} is required`, 'error');
                } else {
                    input.classList.remove('border-red-500');
                }
            });

            if (!isValid) {
                e.preventDefault();
                showNotification(errorMessage, 'error');
            } else {
                logToDebugPanel('Form submitted successfully', 'success');
            }
        });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to show/hide error message
        function showError(element, errorDiv, message) {
            if (message) {
                errorDiv.textContent = message;
                element.classList.add('border-red-500');
            } else {
                errorDiv.textContent = '';
                element.classList.remove('border-red-500');
            }
        }

        // Validation functions
        const validators = {
            username: (value) => {
                const regex = /^[A-Za-z\s]+$/;
                if (!value.trim()) {
                    return "Username is required";
                }
                if (!regex.test(value)) {
                    return "Only letters (A-Z, a-z) and spaces are allowed";
                }
                if (value.length < 3) {
                    return "Username must be at least 3 characters long";
                }
                return "";
            },
            phone: (value) => {
                const regex = /^\d{10}$/;
                if (!value.trim()) {
                    return "Phone number is required";
                }
                if (!regex.test(value)) {
                    return "Phone number must be exactly 10 digits";
                }
                return "";
            },
            phoneOptional: (value) => {
                if (!value.trim()) {
                    return ""; // Optional field
                }
                const regex = /^\d{10}$/;
                if (!regex.test(value)) {
                    return "Phone number must be exactly 10 digits";
                }
                return "";
            }
        };

        // Username validation
        const usernameInput = document.getElementById('username');
        const usernameError = document.getElementById('usernameError');
        
        usernameInput.addEventListener('input', function(e) {
            const value = e.target.value;
            // Remove any characters that aren't letters or spaces
            const sanitizedValue = value.replace(/[^A-Za-z\s]/g, '');
            if (value !== sanitizedValue) {
                this.value = sanitizedValue;
            }
            
            const error = validators.username(this.value);
            showError(this, usernameError, error);
        });

        // Personal phone validation
        const phonePersonalInput = document.getElementById('phone_personal');
        const phonePersonalError = document.getElementById('phonePersonalError');
        
        phonePersonalInput.addEventListener('input', function(e) {
            const value = e.target.value;
            // Remove any non-digit characters
            const sanitizedValue = value.replace(/\D/g, '').substring(0, 10);
            if (value !== sanitizedValue) {
                this.value = sanitizedValue;
            }
            
            const error = validators.phone(this.value);
            showError(this, phonePersonalError, error);
        });

        // Work phone validation (optional)
        const phoneWorkInput = document.getElementById('phone_work');
        const phoneWorkError = document.getElementById('phoneWorkError');
        
        phoneWorkInput.addEventListener('input', function(e) {
            const value = e.target.value;
            // Remove any non-digit characters
            const sanitizedValue = value.replace(/\D/g, '').substring(0, 10);
            if (value !== sanitizedValue) {
                this.value = sanitizedValue;
            }
            
            const error = validators.phoneOptional(this.value);
            showError(this, phoneWorkError, error);
        });

        // Form submission
        const form = document.getElementById('profile-form');
        
        form.addEventListener('submit', function(e) {
            let hasErrors = false;

            // Validate username
            const usernameError = validators.username(usernameInput.value);
            showError(usernameInput, document.getElementById('usernameError'), usernameError);
            if (usernameError) hasErrors = true;

            // Validate personal phone
            const phonePersonalError = validators.phone(phonePersonalInput.value);
            showError(phonePersonalInput, document.getElementById('phonePersonalError'), phonePersonalError);
            if (phonePersonalError) hasErrors = true;

            // Validate work phone (optional)
            const phoneWorkError = validators.phoneOptional(phoneWorkInput.value);
            showError(phoneWorkInput, document.getElementById('phoneWorkError'), phoneWorkError);
            if (phoneWorkError) hasErrors = true;

            // Validate required fields
            const requiredInputs = document.querySelectorAll('input[required], textarea[required]');
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('border-red-500');
                    const errorId = input.id + 'Error';
                    const errorElement = document.getElementById(errorId);
                    if (errorElement) {
                        errorElement.textContent = "This field is required";
                    }
                    hasErrors = true;
                }
            });

            if (hasErrors) {
                e.preventDefault();
                // Scroll to the first error
                const firstError = document.querySelector('.border-red-500');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    });
    </script>

    <script>
    function confirmDelete() {
        if (confirm('Are you sure you want to delete your profile? This action cannot be undone.')) {
            // Create and submit a form to handle the deletion
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = window.location.href;
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'delete_profile';
            input.value = '1';
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
</html> 



