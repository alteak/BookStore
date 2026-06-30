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
    // Get count of unread messages from admin for this user
    $query = "SELECT COUNT(*) as unread_count 
              FROM messages 
              WHERE user_email = ? 
              AND sender = 'ADMIN' 
              AND is_read = 0";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception('Database preparation failed: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "s", $userEmail);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $row = mysqli_fetch_assoc($result);
    $unreadCount = (int)$row['unread_count'];
    
    mysqli_stmt_close($stmt);
    
    echo json_encode([
        'success' => true,
        'count' => $unreadCount
    ]);
    
} catch (Exception $e) {
    error_log('Error in getUnreadCount.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to get unread count'
    ]);
}

mysqli_close($conn);
?>