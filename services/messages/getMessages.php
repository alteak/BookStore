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

$userEmail = $_SESSION['user']['email'];

try {
    // Get messages for this user, ordered by creation time
    $query = "SELECT sender, message, created_at, is_read 
              FROM messages 
              WHERE user_email = ? 
              ORDER BY created_at ASC";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception('Database preparation failed: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "s", $userEmail);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $messages = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = [
            'sender' => $row['sender'],
            'message' => $row['message'],
            'created_at' => $row['created_at'],
            'is_read' => (bool)$row['is_read']
        ];
    }
    
    // Mark all messages as read for this user
    $markReadQuery = "UPDATE messages SET is_read = 1 WHERE user_email = ? AND is_read = 0";
    $markStmt = mysqli_prepare($conn, $markReadQuery);
    if ($markStmt) {
        mysqli_stmt_bind_param($markStmt, "s", $userEmail);
        mysqli_stmt_execute($markStmt);
        mysqli_stmt_close($markStmt);
    }
    
    mysqli_stmt_close($stmt);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
    
} catch (Exception $e) {
    error_log('Error in getMessages.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve messages'
    ]);
}

mysqli_close($conn);
?>