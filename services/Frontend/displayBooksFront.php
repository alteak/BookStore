<?php

require_once __DIR__ . '/../session/sessionHandler.php';
requireLogin();

require_once __DIR__ . '/../../database/databaseConnection.php';

$sql = "
    SELECT
        b.id,
        b.title,
        b.cover_image,
        COALESCE(i.price, 0) AS price,
        COALESCE(i.stock, 0) AS stock,
        GROUP_CONCAT(g.name ORDER BY g.name SEPARATOR ', ') AS genres
    FROM books b
    LEFT JOIN inventory i ON i.book_id = b.id
    LEFT JOIN book_genres bg ON bg.book_id = b.id
    LEFT JOIN genres g ON g.id = bg.genre_id
    WHERE b.is_active = 1
    GROUP BY b.id
    ORDER BY b.id DESC
";

$res = mysqli_query($conn, $sql);

$books = [];

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $books[] = [
            'id'     => (int)$row['id'],
            'title'  => $row['title'],
            'price'  => (float)$row['price'],
            'stock'  => (int)$row['stock'],
            'image'  => $row['cover_image']
                ? $row['cover_image']
                : '/assets/img/librat/placeholder.png',
            'genres' => $row['genres']
                ? array_map('trim', explode(',', $row['genres']))
                : ['Others']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($books);
