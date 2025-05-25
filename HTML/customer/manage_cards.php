<?php
session_start();
require_once '../connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Initialize messages
$success_message = '';
$error_message = '';

// Add decryption function and key at the top after session and DB connection
$encryption_key = "your_encryption_key_here_32_chars_long"; // Use your actual key
function decrypt_card_number($encrypted_card, $encryption_key) {
    return openssl_decrypt(
        $encrypted_card,
        'AES-256-CBC',
        $encryption_key,
        0,
        substr($encryption_key, 0, 16)
    );
}

// Handle card deletion
if (isset($_POST['delete_card'])) {
    $card_id = $_POST['card_id'];
    
    // Verify that this card belongs to the current user before deletion
    $verify_sql = "SELECT id FROM saved_card WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($verify_sql);
    $stmt->bind_param("ii", $card_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Card belongs to user, proceed with deletion
        $delete_sql = "DELETE FROM saved_card WHERE id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("i", $card_id);
        
        if ($stmt->execute()) {
            $success_message = "Card successfully deleted.";
        } else {
            $error_message = "Error deleting card: " . $conn->error;
        }
    } else {
        $error_message = "You don't have permission to delete this card.";
    }
}

// Handle card edit
if (isset($_POST['edit_card'])) {
    $card_id = $_POST['card_id'];
    $card_holder_name = trim($_POST['card_holder_name']);
    $expiry_date = $_POST['expiry_date'];
    
    // Validate inputs
    $errors = [];
    
    if (empty($card_holder_name)) {
        $errors[] = "Cardholder name is required.";
    } elseif (!preg_match("/^[A-Za-z\s]{3,50}$/", $card_holder_name)) {
        $errors[] = "Cardholder name must contain only letters and spaces (3-50 characters).";
    }
    
    if (empty($expiry_date)) {
        $errors[] = "Expiry date is required.";
    } elseif (!preg_match("/^(0[1-9]|1[0-2])\/[0-9]{2}$/", $expiry_date)) {
        $errors[] = "Expiry date must be in format MM/YY.";
    } else {
        // Check if the card is expired
        list($month, $year) = explode('/', $expiry_date);
        $expiry_timestamp = mktime(0, 0, 0, $month, 1, 2000 + intval($year));
        
        if ($expiry_timestamp < time()) {
            $errors[] = "Card has expired. Please provide a valid expiry date.";
        }
    }
    
    // Verify that this card belongs to the current user before updating
    $verify_sql = "SELECT id FROM saved_card WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($verify_sql);
    $stmt->bind_param("ii", $card_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $errors[] = "You don't have permission to edit this card.";
    }
    
    if (empty($errors)) {
        // Update card information
        $update_sql = "UPDATE saved_card SET card_holder_name = ?, expiry_date = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssii", $card_holder_name, $expiry_date, $card_id, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "Card information updated successfully.";
        } else {
            $error_message = "Error updating card: " . $conn->error;
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Get all saved cards for this user
$saved_cards = [];
$cards_sql = "SELECT * FROM saved_card WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($cards_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($card = $result->fetch_assoc()) {
        $saved_cards[] = $card;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ceylon Cart - Manage Payment Cards</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&family=Abel&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            margin: 0;
            font-family: 'Abel', sans-serif;
            background-color: #f0f0f0;
            color: #333;
        }
        .header {
            background-color: #F35821;
            position: sticky;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .logo-container {
            display: flex;
            align-items: center;
        }
        .logo {
            width: 200px;
            height: auto;
        }
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
        .header-buttons {
            display: flex;
            gap: 16px;
            align-items: center;
            margin-right: 24px;
        }
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
        .profile-dropdown {
            position: relative;
            display: inline-block;
            margin-right: 10px;
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
        .card-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .manage-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link i {
            margin-right: 5px;
        }
        
        .back-link:hover {
            color: #F35821;
        }
        
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .success-message {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .error-message {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .card-list {
            display: flex;
            flex-wrap: nowrap;
            gap: 1.5rem;
            overflow-x: auto;
            padding-bottom: 1rem;
        }
        
        .card-item {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background-color: #fff;
            position: relative;
            min-width: 20rem;
            max-width: 20rem;
            flex: 0 0 20rem;
        }
        
        .card-item.edit-mode {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .card-type {
            font-size: 24px;
        }
        
        .card-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: #666;
            padding: 5px;
        }
        
        .edit-btn:hover {
            color: #007bff;
        }
        
        .delete-btn:hover {
            color: #dc3545;
        }
        
        .cancel-btn:hover {
            color: #6c757d;
        }
        
        .save-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .save-btn:hover {
            background-color: #218838;
        }
        
        .card-body {
            margin-bottom: 10px;
        }
        
        .card-detail {
            margin-bottom: 8px;
        }
        
        .card-label {
            font-weight: bold;
            color: #666;
            display: block;
            margin-bottom: 3px;
            font-size: 12px;
        }
        
        .card-value {
            font-size: 16px;
        }
        
        .card-form {
            display: none;
        }
        
        .card-item.edit-mode .card-info {
            display: none;
        }
        
        .card-item.edit-mode .card-form {
            display: block;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #666;
            font-size: 12px;
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
            margin-top: 15px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            background-color: #f9f9f9;
            border-radius: 8px;
            border: 1px dashed #ddd;
        }
        
        .empty-state i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
        }
        
        .empty-state h3 {
            margin-bottom: 10px;
            color: #666;
        }
        
        .empty-state p {
            color: #888;
            margin-bottom: 20px;
        }
        
        .add-card-btn {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
        }
        
        .add-card-btn:hover {
            background-color: #0056b3;
        }
        
        .delete-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 100;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            width: 90%;
            max-width: 400px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .modal-title {
            font-size: 18px;
            font-weight: bold;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
        }
        
        .modal-body {
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .confirm-delete {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .cancel-delete {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <header class="header">
        <a href="../product_view.php">
            <div class="logo-container">
                <img src="../Images/LogoVertical.png" alt="CeylonCart Logo" class="logo">
            </div>
        </a>
        <div class="header-buttons">
            <a href="../cart.php">
                <button class="cart-button">
                    <img src="../Images/ViewCart.png" alt="View Cart" class="cart-icon">
                </button>
            </a>
            <div class="profile-dropdown">
                <button class="profile-button">
                    <img src="../Images/profile-picture.png" alt="Profile" class="profile-icon">
                </button>
                <div class="dropdown-content">
                    <a href="../user_profile.php">VIEW PROFILE</a>
                    <a href="../orderhistory.php">ORDER HISTORY</a>
                    <a href="../login.php">LOG OUT</a>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Card Management Container -->
    <div class="card-container flex-1">
        <div class="manage-card-header">
            <h1 class="text-2xl font-bold" style="font-family: 'Koulen', sans-serif;">Manage Payment Cards</h1>
            <a href="customerPayment.php" class="back-link font-bold text-base" style="font-family: 'Koulen', sans-serif;">
                <i class="fas fa-arrow-left"></i> Back to Payment
            </a>
        </div>
        
        <!-- Display messages if any -->
        <?php if (!empty($success_message)): ?>
            <div class="message success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="message error-message">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Card List -->
        <?php if (!empty($saved_cards)): ?>
            <div class="card-list">
                <?php foreach ($saved_cards as $card): ?>
                    <div class="card-item" id="card-<?php echo $card['id']; ?>">
                        <div class="card-header">
                            <div class="card-type">
                                <?php if ($card['card_type'] == 'Visa'): ?>
                                    <i class="fab fa-cc-visa" style="color: #1a1f71;"></i>
                                <?php elseif ($card['card_type'] == 'MasterCard'): ?>
                                    <i class="fab fa-cc-mastercard" style="color: #eb001b;"></i>
                                <?php elseif ($card['card_type'] == 'Discover'): ?>
                                    <i class="fab fa-cc-discover" style="color: #ff6000;"></i>
                                <?php elseif ($card['card_type'] == 'Amex'): ?>
                                    <i class="fab fa-cc-amex" style="color: #2e77bc;"></i>
                                <?php else: ?>
                                    <i class="far fa-credit-card"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-actions flex gap-2">
                                <button class="action-btn edit-btn" data-card-id="<?php echo $card['id']; ?>" data-card-holder="<?php echo htmlspecialchars($card['card_holder_name']); ?>" data-expiry="<?php echo htmlspecialchars($card['expiry_date']); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn delete-btn" data-card-id="<?php echo $card['id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Card Info (View Mode) -->
                        <div class="card-info">
                            <div class="card-body">
                                <div class="card-detail">
                                    <span class="card-label">CARD HOLDER</span>
                                    <span class="card-value card-holder-name"><?php echo htmlspecialchars($card['card_holder_name']); ?></span>
                                </div>
                                <div class="card-detail">
                                    <span class="card-label">CARD NUMBER</span>
                                    <span class="card-value">XXXX XXXX XXXX <?php echo htmlspecialchars(substr(decrypt_card_number($card['card_number'], $encryption_key), -4)); ?></span>
                                </div>
                                <div class="card-detail">
                                    <span class="card-label">EXPIRY DATE</span>
                                    <span class="card-value card-expiry"><?php echo htmlspecialchars($card['expiry_date']); ?></span>
                                </div>
                                <div class="card-detail">
                                    <span class="card-label">ADDED ON</span>
                                    <span class="card-value"><?php echo date('M d, Y', strtotime($card['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <i class="far fa-credit-card"></i>
                <h3>No Saved Cards</h3>
                <p>You haven't saved any payment cards yet. Add a card during checkout to save it for future purchases.</p>
                <a href="customerPayment.php" class="add-card-btn">Go to Payment</a>
            </div>
        <?php endif; ?>
    </div>
    
    <footer class="bg-white py-4 px-24 flex justify-between items-center mt-auto">
        <div class="flex gap-4">
            <img src="../Images/facebook.png" alt="Facebook" class="w-7 h-7 hover:scale-110 transition-transform duration-300">
            <img src="../Images/instagram.png" alt="Instagram" class="w-7 h-7 hover:scale-110 transition-transform duration-300">
            <img src="../Images/twitter.png" alt="Twitter" class="w-7 h-7 hover:scale-110 transition-transform duration-300">
        </div>
        <p class="text-gray-600">© CeylonCart 2025</p>
    </footer>
    <!-- Edit Card Modal -->
    <div id="editCardModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
        <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md relative">
            <button id="closeEditModal" class="absolute top-2 right-2 text-gray-500 hover:text-red-500 text-2xl">&times;</button>
            <h2 class="text-xl font-bold mb-4" style="font-family: 'Koulen', sans-serif;">Edit Card</h2>
            <form id="editCardForm">
                <input type="hidden" name="card_id" id="editCardId">
                <div class="mb-4">
                    <label for="editCardHolder" class="block font-bold mb-1">Cardholder Name</label>
                    <input type="text" id="editCardHolder" name="card_holder_name" class="w-full border rounded px-3 py-2" required>
                </div>
                <div class="mb-4">
                    <label for="editExpiryDate" class="block font-bold mb-1">Expiry Date (MM/YY)</label>
                    <input type="text" id="editExpiryDate" name="expiry_date" class="w-full border rounded px-3 py-2" maxlength="5" pattern="(0[1-9]|1[0-2])/[0-9]{2}" required>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" id="cancelEditBtn" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded bg-orange-600 text-white font-bold hover:bg-orange-700">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Delete Confirmation Modal -->
    <div id="deleteCardModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
        <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md relative">
            <button id="closeDeleteModal" class="absolute top-2 right-2 text-gray-500 hover:text-red-500 text-2xl">&times;</button>
            <h2 class="text-xl font-bold mb-4" style="font-family: 'Koulen', sans-serif;">Delete Card</h2>
            <p class="mb-6">Are you sure you want to delete this card? This action cannot be undone.</p>
            <div class="flex justify-end gap-2">
                <button id="cancelDeleteBtn" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Cancel</button>
                <button id="confirmDeleteBtn" class="px-4 py-2 rounded bg-red-500 text-white font-bold hover:bg-red-700">Delete</button>
            </div>
        </div>
    </div>
    <script>
    $(document).ready(function() {
        let cardIdToDelete = null;
        // Open Edit Modal
        $('.edit-btn').on('click', function() {
            const cardId = $(this).data('card-id');
            const cardHolder = $(this).data('card-holder');
            const expiry = $(this).data('expiry');
            $('#editCardId').val(cardId);
            $('#editCardHolder').val(cardHolder);
            $('#editExpiryDate').val(expiry);
            $('#editCardModal').removeClass('hidden');
        });
        // Close Edit Modal
        $('#closeEditModal, #cancelEditBtn').on('click', function() {
            $('#editCardModal').addClass('hidden');
        });
        // Submit Edit Form
        $('#editCardForm').on('submit', function(e) {
            e.preventDefault();
            const cardId = $('#editCardId').val();
            const cardHolder = $('#editCardHolder').val();
            const expiry = $('#editExpiryDate').val();
            $.ajax({
                url: 'manage_cards_ajax.php',
                type: 'POST',
                data: {
                    operation: 'update',
                    card_id: cardId,
                    card_holder_name: cardHolder,
                    expiry_date: expiry
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update UI
                        const cardItem = $('#card-' + cardId);
                        cardItem.find('.card-holder-name').text(cardHolder);
                        cardItem.find('.card-expiry').text(expiry);
                        $('#editCardModal').addClass('hidden');
                        showMessage('Card updated successfully.', 'success');
                    } else {
                        showMessage(response.message || 'Error updating card.', 'error');
                    }
                },
                error: function() {
                    showMessage('There was an error processing your request.', 'error');
                }
            });
        });
        // Open Delete Modal
        $('.delete-btn').on('click', function() {
            cardIdToDelete = $(this).data('card-id');
            $('#deleteCardModal').removeClass('hidden');
        });
        // Close Delete Modal
        $('#closeDeleteModal, #cancelDeleteBtn').on('click', function() {
            $('#deleteCardModal').addClass('hidden');
            cardIdToDelete = null;
        });
        // Confirm Delete
        $('#confirmDeleteBtn').on('click', function() {
            if (!cardIdToDelete) return;
            $.ajax({
                url: 'manage_cards_ajax.php',
                type: 'POST',
                data: {
                    operation: 'delete',
                    card_id: cardIdToDelete
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#card-' + cardIdToDelete).fadeOut(400, function() {
                            $(this).remove();
                        });
                        showMessage('Card deleted successfully.', 'success');
                    } else {
                        showMessage(response.message || 'Error deleting card.', 'error');
                    }
                    $('#deleteCardModal').addClass('hidden');
                    cardIdToDelete = null;
                },
                error: function() {
                    showMessage('There was an error processing your request.', 'error');
                    $('#deleteCardModal').addClass('hidden');
                    cardIdToDelete = null;
                }
            });
        });
        // Utility: Show message
        function showMessage(msg, type) {
            const color = type === 'success' ? 'bg-green-100 text-green-800 border-green-300' : 'bg-red-100 text-red-800 border-red-300';
            const messageDiv = $('<div class="message ' + color + ' border px-4 py-2 rounded mb-4">' + msg + '</div>');
            $('.manage-card-header').after(messageDiv);
            setTimeout(function() { messageDiv.fadeOut(500, function() { $(this).remove(); }); }, 4000);
        }
    });
    </script>
</body>
</html>