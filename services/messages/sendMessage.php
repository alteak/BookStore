<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../database/databaseConnection.php';
require_once __DIR__ . '/../session/sessionHandler.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$userEmail = $_SESSION['user']['email'];

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['message'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message is required']);
        exit;
    }
    
    $message = trim($input['message']);
    
    // Validate message
    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
        exit;
    }
    
    if (strlen($message) > 500) {
        echo json_encode(['success' => false, 'message' => 'Message is too long (max 500 characters)']);
        exit;
    }
    
    // Insert the message
    $query = "INSERT INTO messages (user_email, sender, message, is_read, created_at) 
              VALUES (?, 'USER', ?, 0, NOW())";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception('Database preparation failed: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "ss", $userEmail, $message);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        echo json_encode([
            'success' => true,
            'message' => 'Message sent successfully'
        ]);
    } else {
        throw new Exception('Failed to insert message: ' . mysqli_stmt_error($stmt));
    }
    
} catch (Exception $e) {
    error_log('Error in sendMessage.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send message'
    ]);
}

mysqli_close($conn);
?>