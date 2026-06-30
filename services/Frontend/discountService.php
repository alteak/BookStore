<?php
require_once __DIR__ . '/../session/sessionHandler.php';
require_once __DIR__ . '/../../database/databaseConnection.php';

requireLogin();

// JSON response helper
function jsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ] + $data);
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['action']) || $input['action'] !== 'validate_discount') {
        jsonResponse(false, 'Invalid action');
    }
    
    $code = strtoupper(trim($input['code'] ?? ''));
    $subtotal = floatval($input['subtotal'] ?? 0);
    
    if (empty($code)) {
        jsonResponse(false, 'Shkruani një kod zbritjeje');
    }
    
    if ($subtotal <= 0) {
        jsonResponse(false, 'Shporta është bosh');
    }
    
    // Query the discount from database
    $stmt = $conn->prepare("
        SELECT id, code, type, value
        FROM discounts 
        WHERE code = ? AND is_active = 1
    ");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        jsonResponse(false, 'Code is not valid');
    }
    
    $discount = $result->fetch_assoc();
    
    // Calculate discount amount
    $discountAmount = 0;
    if ($discount['type'] === 'PERCENT') {
        $discountAmount = ($subtotal * $discount['value']) / 100;
    } elseif ($discount['type'] === 'FIXED') {
        $discountAmount = $discount['value'];
    }
    
    // Ensure discount doesn't exceed subtotal
    $discountAmount = min($discountAmount, $subtotal);
    
    echo json_encode([
        'success' => true,
        'discount_amount' => $discountAmount,
        'discount_code' => $discount['code'],
        'message' => 'Code applied successfully'
    ]);
    exit;
}

jsonResponse(false, 'Invalid request method');

