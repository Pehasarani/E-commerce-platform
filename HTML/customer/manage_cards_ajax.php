<?php
session_start();
require_once '../connection.php';
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Initialize response array
$response = ['success' => false, 'message' => 'Invalid request'];

// Handle AJAX operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Get the operation type
    $operation = isset($_POST['operation']) ? $_POST['operation'] : '';
    
    switch ($operation) {
        case 'delete':
            if (isset($_POST['card_id'])) {
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
                        $response = ['success' => true, 'message' => 'Card successfully deleted'];
                    } else {
                        $response = ['success' => false, 'message' => 'Error deleting card: ' . $conn->error];
                    }
                } else {
                    $response = ['success' => false, 'message' => 'You don\'t have permission to delete this card'];
                }
            }
            break;
            
        case 'update':
            if (isset($_POST['card_id']) && isset($_POST['card_holder_name']) && isset($_POST['expiry_date'])) {
                $card_id = $_POST['card_id'];
                $card_holder_name = trim($_POST['card_holder_name']);
                $expiry_date = $_POST['expiry_date'];
                
                // Validate inputs
                $errors = [];
                
                if (empty($card_holder_name)) {
                    $errors[] = "Cardholder name is required";
                } elseif (!preg_match("/^[A-Za-z\s]{3,50}$/", $card_holder_name)) {
                    $errors[] = "Cardholder name must contain only letters and spaces (3-50 characters)";
                }
                
                if (empty($expiry_date)) {
                    $errors[] = "Expiry date is required";
                } elseif (!preg_match("/^(0[1-9]|1[0-2])\/[0-9]{2}$/", $expiry_date)) {
                    $errors[] = "Expiry date must be in format MM/YY";
                } else {
                    // Check if the card is expired
                    list($month, $year) = explode('/', $expiry_date);
                    $expiry_timestamp = mktime(0, 0, 0, $month, 1, 2000 + intval($year));
                    
                    if ($expiry_timestamp < time()) {
                        $errors[] = "Card has expired. Please provide a valid expiry date";
                    }
                }
                
                // Verify that this card belongs to the current user before updating
                $verify_sql = "SELECT id FROM saved_card WHERE id = ? AND user_id = ?";
                $stmt = $conn->prepare($verify_sql);
                if (!$stmt) {
                    $response = ['success' => false, 'message' => 'Prepare failed: ' . $conn->error];
                    break;
                }
                $stmt->bind_param("ii", $card_id, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result === false) {
                    $response = ['success' => false, 'message' => 'Get result failed: ' . $stmt->error];
                    break;
                }
                if ($result->num_rows === 0) {
                    $errors[] = "You don't have permission to edit this card";
                }
                
                if (empty($errors)) {
                    // Update card information
                    $update_sql = "UPDATE saved_card SET card_holder_name = ?, expiry_date = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
                    $stmt = $conn->prepare($update_sql);
                    if (!$stmt) {
                        $response = ['success' => false, 'message' => 'Prepare failed: ' . $conn->error];
                        break;
                    }
                    $stmt->bind_param("ssii", $card_holder_name, $expiry_date, $card_id, $user_id);
                    
                    if ($stmt->execute()) {
                        $response = ['success' => true, 'message' => 'Card updated successfully'];
                    } else {
                        $response = ['success' => false, 'message' => 'Error updating card: ' . $stmt->error];
                    }
                } else {
                    $response = ['success' => false, 'message' => implode(", ", $errors)];
                }
            } else {
                $response = ['success' => false, 'message' => 'Missing required POST parameters for update.'];
            }
            break;
            
        case 'get_card':
            if (isset($_POST['card_id'])) {
                $card_id = $_POST['card_id'];
                
                // Get card details
                $card_sql = "SELECT * FROM saved_card WHERE id = ? AND user_id = ?";
                $stmt = $conn->prepare($card_sql);
                $stmt->bind_param("ii", $card_id, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $card = $result->fetch_assoc();
                    $response = [
                        'success' => true, 
                        'card' => [
                            'id' => $card['id'],
                            'card_holder_name' => $card['card_holder_name'],
                            'last_four_digits' => $card['last_four_digits'],
                            'card_type' => $card['card_type'],
                            'expiry_date' => $card['expiry_date']
                        ]
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'Card not found or access denied'];
                }
            }
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Unknown operation'];
    }
}

// Return JSON response
echo json_encode($response);
?>