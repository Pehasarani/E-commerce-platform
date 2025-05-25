<?php
session_start();
require_once '../connection.php';

// Initialize variables
$success_message = '';
$error_message = '';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete card
    if (isset($_POST['delete_card'])) {
        $card_id = $_POST['card_id'];
        $delete_sql = "DELETE FROM saved_cards WHERE id = ? AND cus_id = ?";
        
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("ii", $card_id, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "Card removed successfully.";
        } else {
            $error_message = "Error removing card: " . $conn->error;
        }
        $stmt->close();
    }
    
    // Set card as default
    if (isset($_POST['set_default'])) {
        $card_id = $_POST['card_id'];
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // First, set all cards to non-default
            $reset_sql = "UPDATE saved_cards SET is_default = 0 WHERE cus_id = ?";
            $reset_stmt = $conn->prepare($reset_sql);
            $reset_stmt->bind_param("i", $user_id);
            $reset_stmt->execute();
            $reset_stmt->close();
            
            // Then set selected card as default
            $default_sql = "UPDATE saved_cards SET is_default = 1 WHERE id = ? AND cus_id = ?";
            $default_stmt = $conn->prepare($default_sql);
            $default_stmt->bind_param("ii", $card_id, $user_id);
            $default_stmt->execute();
            $default_stmt->close();
            
            $conn->commit();
            $success_message = "Default payment method updated.";
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error updating default card: " . $e->getMessage();
        }
    }
    
    // Update card
    if (isset($_POST['update_card'])) {
        $card_id = $_POST['card_id'];
        $card_holder_name = trim($_POST['card_holder_name']);
        $expiry_date = $_POST['expiry_date'];
        
        $update_sql = "UPDATE saved_cards SET card_holder_name = ?, expiry_date = ? WHERE id = ? AND cus_id = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssii", $card_holder_name, $expiry_date, $card_id, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "Card updated successfully.";
        } else {
            $error_message = "Error updating card: " . $conn->error;
        }
        $stmt->close();
    }
}

// Get all saved cards for this user
$cards_sql = "SELECT * FROM saved_cards WHERE cus_id = ? ORDER BY is_default DESC, created_at DESC";
$cards_stmt = $conn->prepare($cards_sql);
$cards_stmt->bind_param("i", $user_id);
$cards_stmt->execute();
$cards_result = $cards_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ceylon Cart - Saved Payment Methods</title>
    <link rel="stylesheet" href="style.css"> <!-- Link to your CSS file -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .saved-cards-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .card-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
        }
        
        .card-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .card-item:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .card-details {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .card-logo {
            font-size: 24px;
            color: #007bff;
        }
        
        .card-info {
            display: flex;
            flex-direction: column;
        }
        
        .card-number {
            font-size: 18px;
            font-weight: bold;
        }
        
        .card-name, .card-expiry {
            color: #666;
            font-size: 14px;
        }
        
        .card-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            background-color: transparent;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s ease;
        }
        
        .btn-edit {
            color: #007bff;
        }
        
        .btn-delete {
            color: #dc3545;
        }
        
        .btn-default {
            color: #28a745;
        }
        
        .btn:hover {
            transform: scale(1.1);
        }
        
        .default-badge {
            background-color: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 10px;
        }
        
        .success-message, .error-message {
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .edit-form {
            display: none;
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        
        .form-group {
            margin-bottom: 10px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .add-card-btn {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 20px;
            text-decoration: none;
            display: inline-block;
        }
        
        .add-card-btn:hover {
            background-color: #0056b3;
        }
        
        .no-cards {
            text-align: center;
            padding: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-logo">
            <img src="./../images/logo.png" alt="Logo" class="logo">
        </div>
        <div class="navbar-search">
            <input type="text" placeholder="Search in CeylonCart">
            <button><i class="fas fa-search"></i></button>
        </div>
        <div class="navbar-icons">
            <i class="fas fa-cart-shopping"></i>
            <i class="fas fa-user-circle"></i>
        </div>
    </nav>

    <!-- Dashboard Container -->
    <div class="dashboard-container">
        <main class="main-content">
            <!-- Saved Cards Section -->
            <div class="saved-cards-container">
                <a href="cart.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Cart
                </a>
                <h1>Saved Payment Methods</h1>
                
                <?php if ($success_message): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="card-list">
                    <?php if ($cards_result->num_rows > 0): ?>
                        <?php while ($card = $cards_result->fetch_assoc()): ?>
                            <div class="card-item" id="card-<?php echo $card['id']; ?>">
                                <div class="card-details">
                                    <div class="card-logo">
                                        <?php if ($card['card_type'] == 'Visa'): ?>
                                            <i class="fab fa-cc-visa"></i>
                                        <?php elseif ($card['card_type'] == 'MasterCard'): ?>
                                            <i class="fab fa-cc-mastercard"></i>
                                        <?php elseif ($card['card_type'] == 'Amex'): ?>
                                            <i class="fab fa-cc-amex"></i>
                                        <?php elseif ($card['card_type'] == 'Discover'): ?>
                                            <i class="fab fa-cc-discover"></i>
                                        <?php else: ?>
                                            <i class="fas fa-credit-card"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-info">
                                        <span class="card-number">
                                            <?php echo $card['card_type']; ?> •••• <?php echo htmlspecialchars($card['card_last_four']); ?>
                                            <?php if ($card['is_default']): ?>
                                                <span class="default-badge">Default</span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="card-name"><?php echo htmlspecialchars($card['card_holder_name']); ?></span>
                                        <span class="card-expiry">Expires: <?php echo htmlspecialchars($card['expiry_date']); ?></span>
                                    </div>
                                </div>
                                <div class="card-actions">
                                    <?php if (!$card['is_default']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
                                            <button type="submit" name="set_default" class="btn btn-default" title="Set as Default">
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <button class="btn btn-edit" onclick="toggleEditForm(<?php echo $card['id']; ?>)" title="Edit Card">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this card?');">
                                        <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
                                        <button type="submit" name="delete_card" class="btn btn-delete" title="Delete Card">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                                
                                <!-- Edit Form (Hidden by default) -->
                                <div class="edit-form" id="edit-form-<?php echo $card['id']; ?>">
                                    <form method="POST">
                                        <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
                                        <div class="form-group">
                                            <label for="card-holder-name-<?php echo $card['id']; ?>">Cardholder Name</label>
                                            <input type="text" id="card-holder-name-<?php echo $card['id']; ?>" name="card_holder_name" 
                                                   value="<?php echo htmlspecialchars($card['card_holder_name']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="expiry-date-<?php echo $card['id']; ?>">Expiry Date</label>
                                            <input type="text" id="expiry-date-<?php echo $card['id']; ?>" name="expiry_date" 
                                                   value="<?php echo htmlspecialchars($card['expiry_date']); ?>" 
                                                   placeholder="MM/YY" maxlength="5" required>
                                        </div>
                                        <div class="form-actions">
                                            <button type="button" class="btn" onclick="toggleEditForm(<?php echo $card['id']; ?>)">Cancel</button>
                                            <button type="submit" name="update_card" class="btn btn-edit">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-cards">
                            <p>You don't have any saved payment methods yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <a href="customerPayment.php" class="add-card-btn">
                    <i class="fas fa-plus"></i> Add New Payment Method
                </a>
            </div>
        </main>
    </div>

    <script>
        function toggleEditForm(cardId) {
            const editForm = document.getElementById(`edit-form-${cardId}`);
            if (editForm.style.display === 'block') {
                editForm.style.display = 'none';
            } else {
                // Hide all other edit forms first
                const allForms = document.querySelectorAll('.edit-form');
                allForms.forEach(form => {
                    form.style.display = 'none';
                });
                
                // Show this edit form
                editForm.style.display = 'block';
            }
        }
        
        // Expiry date validation and formatting
        document.addEventListener('DOMContentLoaded', function() {
            const expiryInputs = document.querySelectorAll('input[name="expiry_date"]');
            
            expiryInputs.forEach(input => {
                input.addEventListener('input', function() {
                    let value = this.value.replace(/\D/g, '');
                    if (value.length >= 2) {
                        value = value.slice(0,2) + '/' + value.slice(2);
                    }
                    this.value = value;
                    
                    // Validate date
                    if (value.length === 5) {
                        let [month, year] = value.split('/');
                        let currentDate = new Date();
                        let currentYear = currentDate.getFullYear() % 100;
                        let currentMonth = currentDate.getMonth() + 1;
                        
                        if (parseInt(month) < 1 || parseInt(month) > 12 || 
                            (parseInt(year) < currentYear || 
                            (parseInt(year) === currentYear && parseInt(month) < currentMonth))) {
                            this.setCustomValidity('Invalid or expired date');
                        } else {
                            this.setCustomValidity('');
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>