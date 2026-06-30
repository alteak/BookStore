<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../../database/databaseConnection.php";
require_once __DIR__ . "/../../services/session/sessionHandler.php";

/** @var mysqli $conn */

header('Content-Type: application/json');
requireLogin();

// Helpers
function jsonResponse($success, $data = null, $message = '')
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data
    ]);
    exit;
}

// Get user email from session
$userEmail = $_SESSION['user']['email'] ?? null;
if (!$userEmail) {
    jsonResponse(false, null, 'User not authenticated');
}

// Router
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    addToCart($conn, $userEmail);
} elseif ($method === 'GET') {
    getCart($conn, $userEmail);
} elseif ($method === 'PUT') {
    updateCartQuantity($conn, $userEmail);
} elseif ($method === 'DELETE') {
    removeFromCart($conn, $userEmail);
} else {
    jsonResponse(false, null, 'Method not allowed');
}

// Add to cart
function addToCart(mysqli $conn, string $userEmail)
{
    $input = json_decode(file_get_contents("php://input"), true);

    $bookId = isset($input['book_id']) ? (int)$input['book_id'] : 0;
    $quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;
    
    if ($bookId <= 0) {
        jsonResponse(false, null, 'Invalid book id');
    }

    // Check if book already in cart
    $checkSql = "SELECT quantity FROM cart WHERE user_email = ? AND book_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("si", $userEmail, $bookId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        // Update quantity
        $row = $result->fetch_assoc();
        $newQty = $row['quantity'] + $quantity;
        
        $updateSql = "UPDATE cart SET quantity = ? WHERE user_email = ? AND book_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("isi", $newQty, $userEmail, $bookId);
        $updateStmt->execute();
        
        jsonResponse(true, null, 'Cart updated');
    } else {
        // Insert new item
        $insertSql = "INSERT INTO cart (user_email, book_id, quantity) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("sii", $userEmail, $bookId, $quantity);
        $insertStmt->execute();
        
        jsonResponse(true, null, 'Added to cart');
    }
}

// Get cart
function getCart(mysqli $conn, string $userEmail)
{
    $sql = "
        SELECT
            c.book_id AS id,
            c.quantity AS qty,
            b.title,
            b.cover_image AS image,
            COALESCE(i.price, 0) AS price,
            COALESCE(i.stock, 0) AS stock,
            COALESCE(GROUP_CONCAT(g.name SEPARATOR ', '), 'Book') AS category
        FROM cart c
        INNER JOIN books b ON b.id = c.book_id
        LEFT JOIN inventory i ON i.book_id = b.id
        LEFT JOIN book_genres bg ON bg.book_id = b.id
        LEFT JOIN genres g ON g.id = bg.genre_id
        WHERE c.user_email = ?
        GROUP BY c.book_id
        ORDER BY c.id DESC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        jsonResponse(false, null, 'Prepare failed');
    }

    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $res = $stmt->get_result();

    $cart = [];
    while ($row = $res->fetch_assoc()) {
        $cart[] = [
            'id'       => (int)$row['id'],
            'title'    => $row['title'],
            'price'    => (float)$row['price'],
            'stock'    => (int)$row['stock'],
            'image'    => $row['image'] ?: '/assets/img/librat/placeholder.png',
            'category' => $row['category'] ?: 'Book',
            'qty'      => (int)$row['qty']
        ];
    }

    jsonResponse(true, $cart);
}

// Update cart quantity
function updateCartQuantity(mysqli $conn, string $userEmail)
{
    $input = json_decode(file_get_contents("php://input"), true);

    $bookId = isset($input['book_id']) ? (int)$input['book_id'] : 0;
    $quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;
    
    if ($bookId <= 0 || $quantity < 1) {
        jsonResponse(false, null, 'Invalid parameters');
    }

    $sql = "UPDATE cart SET quantity = ? WHERE user_email = ? AND book_id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        jsonResponse(false, null, 'Prepare failed');
    }

    $stmt->bind_param("isi", $quantity, $userEmail, $bookId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        jsonResponse(true, null, 'Quantity updated');
    } else {
        jsonResponse(false, null, 'Item not found in cart');
    }
}

// Remove from cart
function removeFromCart(mysqli $conn, string $userEmail)
{
    $input = json_decode(file_get_contents("php://input"), true);

    $bookId = isset($input['book_id']) ? (int)$input['book_id'] : 0;
    if ($bookId <= 0) {
        jsonResponse(false, null, 'Invalid book id');
    }

    $sql = "DELETE FROM cart WHERE user_email = ? AND book_id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        jsonResponse(false, null, 'Prepare failed');
    }

    $stmt->bind_param("si", $userEmail, $bookId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        jsonResponse(true, null, 'Removed from cart');
    } else {
        jsonResponse(false, null, 'Item not found in cart');
    }
}
