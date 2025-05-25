<?php
session_start();
include('connection.php');

// Check if supplier is logged in
if (!isset($_SESSION['supplier_id']) || !isset($_SESSION['business_email'])) {
    header("Location: supplierLogin.php");
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_email = $_SESSION['business_email'];
    
    // Get and sanitize form data
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $weight = filter_var($_POST['weight'], FILTER_VALIDATE_FLOAT);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $no_of_products = filter_var($_POST['no_of_products'], FILTER_VALIDATE_INT);
    $product_images = $_POST['product_images'];

    // Enhanced validation
    $errors = [];

    // Validate product name (only letters and spaces)
    if (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
        $errors[] = "Product name can only contain letters and spaces";
    }
    if (strlen($name) < 3 || strlen($name) > 50) {
        $errors[] = "Product name must be between 3 and 50 characters";
    }

    // Validate numeric fields
    if ($weight === false || $weight <= 100 || $weight > 10000) {
        $errors[] = "Weight must be between 100 and 10,000 grams";
    }
    if ($price === false || $price <= 0 || $price > 1000000) {
        $errors[] = "Price must be between 0 and 1,000,000 LKR";
    }
    if ($no_of_products === false || $no_of_products <= 0 || $no_of_products > 1000) {
        $errors[] = "Quantity must be between 1 and 1,000 units";
    }

    if (empty($errors)) {
        try {
            // Generate a numeric product ID instead of string with PROD prefix
            // This assumes your product_id is an INT type in the database
            $product_id = mt_rand(100000, 999999); // Generate a random 6-digit number
            
            $sql = "INSERT INTO `product` (
                        `product_id`, 
                        `name`, 
                        `description`, 
                        `weight`, 
                        `price`, 
                        `no_of_products`, 
                        `product_images`,
                        `top_email`
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . $conn->error);
            }
            
            $stmt->bind_param(
                "issdidss",  // Changed 's' to 'i' for product_id parameter (integer)
                $product_id,
                $name,
                $description,
                $weight,
                $price,
                $no_of_products,
                $product_images,
                $business_email
            );
            
            if ($stmt->execute()) {
                $message = "Product added successfully!";
                $_POST = array();
            } else {
                throw new Exception("Error executing statement: " . $stmt->error);
            }
            
            $stmt->close();
            
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
            error_log("Error in supplierAddProduct.php: " . $e->getMessage());
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .custom-bg {
            background-color: #333;
        }
        .custom-text {
            color: #fff;
        }
        .custom-border {
            border-color: #FF6F00;
        }
        .custom-hover {
            background-color: #ff4b00;
        }
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
                <li class="sidebar-item">
                    <a href="supplierLogout.php" style="text-decoration: none; color: white;">
                        <i class="fas fa-sign-out-alt sidebar-icon"></i>
                        <span class="sidebar-text">Logout</span>
                    </a>
                </li>
            </ul>
        </aside>

        <main class="main-content p-6">
            <div class="max-w-2xl mx-auto">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-semibold mb-6">Add New Product</h2>
                    
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

                    <form method="POST" enctype="multipart/form-data" class="space-y-4" id="productForm">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                                Product Name
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   required
                                   pattern="[a-zA-Z\s]+"
                                   minlength="3"
                                   maxlength="50"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white"
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                            <div id="nameError" class="text-red-500 text-sm mt-1 hidden"></div>
                            <span class="text-sm text-gray-500">Only letters and spaces allowed (3-50 characters)</span>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                                Description
                            </label>
                            <textarea id="description" name="description" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline h-32 bg-white"
                            ><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="weight">
                                    Weight (g)
                                </label>
                                <input type="number" 
                                       id="weight" 
                                       name="weight" 
                                       required
                                       min="100"
                                       max="10000"
                                       step="0.1"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white"
                                       value="<?php echo isset($_POST['weight']) ? htmlspecialchars($_POST['weight']) : ''; ?>">
                                <div id="weightError" class="text-red-500 text-sm mt-1 hidden"></div>
                                <span class="text-sm text-gray-500">Enter weight between 100 and 10,000 grams</span>
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="price">
                                    Price ($)
                                </label>
                                <input type="number" 
                                       id="price" 
                                       name="price" 
                                       required
                                       min="0"
                                       max="1000000"
                                       step="0.01"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white"
                                       value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
                                <div id="priceError" class="text-red-500 text-sm mt-1 hidden"></div>
                                <span class="text-sm text-gray-500">Enter price between 0 and 1,000,000 LKR</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="no_of_products">
                                Quantity
                            </label>
                            <input type="number" 
                                   id="no_of_products" 
                                   name="no_of_products" 
                                   required
                                   min="1"
                                   max="1000"
                                   step="1"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white"
                                   value="<?php echo isset($_POST['no_of_products']) ? htmlspecialchars($_POST['no_of_products']) : ''; ?>">
                            <div id="quantityError" class="text-red-500 text-sm mt-1 hidden"></div>
                            <span class="text-sm text-gray-500">Enter quantity between 1 and 1,000 units</span>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="product_images">
                                Product Image URL
                            </label>
                            <input type="text" id="product_images" name="product_images" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white"
                                placeholder="Enter image URL (e.g., https://example.com/image.jpg)"
                                value="<?php echo isset($_POST['product_images']) ? htmlspecialchars($_POST['product_images']) : ''; ?>">
                        </div>

                        <div class="flex items-center justify-between">
                            <button type="submit" 
                                class="bg-[#FF6F00] hover:bg-[#ff4b00] text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Add Product
                            </button>
                            <a href="supplierDashboard.php" 
                                class="text-[#FF6F00] hover:text-[#ff4b00]">
                                Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('productForm');
    const inputs = {
        name: {
            element: document.getElementById('name'),
            error: document.getElementById('nameError'),
            validate: function(value) {
                if (!value.match(/^[a-zA-Z\s]{3,50}$/)) {
                    return "Product name must contain only letters and spaces (3-50 characters)";
                }
                return "";
            }
        },
        price: {
            element: document.getElementById('price'),
            error: document.getElementById('priceError'),
            validate: function(value) {
                const price = parseFloat(value);
                if (isNaN(price) || price <= 0 || price > 1000000) {
                    return "Price must be between 0 and 1,000,000 LKR";
                }
                return "";
            }
        },
        weight: {
            element: document.getElementById('weight'),
            error: document.getElementById('weightError'),
            validate: function(value) {
                const weight = parseFloat(value);
                if (isNaN(weight) || weight <= 0 || weight > 10000) {
                    return "Weight must be between 100 and 10,000 grams";
                }
                return "";
            }
        },
        quantity: {
            element: document.getElementById('no_of_products'),
            error: document.getElementById('quantityError'),
            validate: function(value) {
                const quantity = parseInt(value);
                if (isNaN(quantity) || quantity < 1 || quantity > 1000) {
                    return "Quantity must be between 1 and 1,000 units";
                }
                return "";
            }
        }
    };

    // Function to show/hide error message and update input styling
    function showError(input, errorElement, message) {
        if (message) {
            errorElement.textContent = message;
            errorElement.classList.remove('hidden');
            input.classList.add('border-red-500');
        } else {
            errorElement.classList.add('hidden');
            input.classList.remove('border-red-500');
        }
    }

    // Add input event listeners for real-time validation
    Object.keys(inputs).forEach(key => {
        const { element, error, validate } = inputs[key];
        
        element.addEventListener('input', function(e) {
            // Remove non-letter characters for name field
            if (key === 'name') {
                this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
            }
            
            // Prevent negative values for numeric fields
            if (key !== 'name' && this.value < 0) {
                this.value = 0;
            }

            // Validate and show/hide error
            const errorMessage = validate(this.value);
            showError(this, error, errorMessage);
        });

        // Also validate on blur
        element.addEventListener('blur', function() {
            const errorMessage = validate(this.value);
            showError(this, error, errorMessage);
        });
    });

    // Form submission validation
    form.addEventListener('submit', function(e) {
        let isValid = true;

        // Validate all fields
        Object.keys(inputs).forEach(key => {
            const { element, error, validate } = inputs[key];
            const errorMessage = validate(element.value);
            showError(element, error, errorMessage);
            if (errorMessage) {
                isValid = false;
            }
        });

        if (!isValid) {
            e.preventDefault();
        }
    });
});
</script>

<style>
.error-input {
    border-color: #FF0000 !important;
}

input:invalid {
    border-color: #FF0000;
}

input:focus:invalid {
    box-shadow: 0 0 3px #FF0000;
}
</style>