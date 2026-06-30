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
    addToWishlist($conn, $userEmail);
} elseif ($method === 'GET') {
    getWishlist($conn, $userEmail);
} elseif ($method === 'DELETE') {
    removeFromWishlist($conn, $userEmail);
} else {
    jsonResponse(false, null, 'Method not allowed');
}

// Add to wishlist
function addToWishlist(mysqli $conn, string $userEmail)
{
    $input = json_decode(file_get_contents("php://input"), true);

    $bookId = isset($input['book_id']) ? (int)$input['book_id'] : 0;
    if ($bookId <= 0) {
        jsonResponse(false, null, 'Invalid book id');
    }

    $sql = "INSERT IGNORE INTO wishlist (user_email, book_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        jsonResponse(false, null, 'Prepare failed');
    }

    $stmt->bind_param("si", $userEmail, $bookId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        jsonResponse(true, null, 'Added to wishlist');
    } else {
        jsonResponse(true, null, 'Already in wishlist');
    }
}

// Get wishlist
function getWishlist(mysqli $conn, string $userEmail)
{
    $sql = "
        SELECT
            b.id,
            b.title,
            b.cover_image AS image,
            COALESCE(i.price, 0) AS price,
            COALESCE(i.stock, 0) AS stock,
            COALESCE(GROUP_CONCAT(g.name SEPARATOR ', '), 'Libër') AS category
        FROM wishlist w
        INNER JOIN books b ON b.id = w.book_id
        LEFT JOIN inventory i ON i.book_id = b.id
        LEFT JOIN book_genres bg ON bg.book_id = b.id
        LEFT JOIN genres g ON g.id = bg.genre_id
        WHERE w.user_email = ?
        GROUP BY b.id
        ORDER BY b.title ASC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        jsonResponse(false, null, 'Prepare failed');
    }

    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $res = $stmt->get_result();

    $wishlist = [];
    while ($row = $res->fetch_assoc()) {
        $wishlist[] = [
            'id'       => (int)$row['id'],
            'title'    => $row['title'],
            'price'    => (float)$row['price'],
            'stock'    => (int)$row['stock'],
            'category' => $row['category'] ?: 'Libër',
            'image'    => $row['image'] ?: '/assets/img/librat/placeholder.png'
        ];
    }

    jsonResponse(true, $wishlist);
}

// Remove from wishlist
function removeFromWishlist(mysqli $conn, string $userEmail)
{
    $input = json_decode(file_get_contents("php://input"), true);

    $bookId = isset($input['book_id']) ? (int)$input['book_id'] : 0;
    if ($bookId <= 0) {
        jsonResponse(false, null, 'Invalid book id');
    }

    $sql = "DELETE FROM wishlist WHERE user_email = ? AND book_id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        jsonResponse(false, null, 'Prepare failed');
    }

    $stmt->bind_param("si", $userEmail, $bookId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        jsonResponse(true, null, 'Removed from wishlist');
    } else {
        jsonResponse(false, null, 'Book not found in wishlist');
    }
}
