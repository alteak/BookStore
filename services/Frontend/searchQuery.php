<?php
 
require_once __DIR__."/../../database/databaseConnection.php";

// MySQL mundeson kerkimin case-insensitive, thjesht kerkoj nqs stringa e dhene ndodhet ne titullin e librit ose emrin e autorit
// Kategorimi i queryve sipas kombinimeve per te optimizuar datasetin (sa me pak te dhena per te perpunuar me kod?)
function searchBooks($search, $conn){
    $searchPattern = "%".$search."%";
    $sql = "SELECT id, title, author, cover_image, i.price
            FROM books 
            JOIN inventory i ON i.book_id = books.id
            WHERE title LIKE ?
            OR author LIKE ?
            ORDER BY is_active, title";    

    $stmt = mysqli_prepare($conn, $sql);    

    mysqli_stmt_bind_param($stmt, "ss", $searchPattern, $searchPattern);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $books = [];
    while($book = mysqli_fetch_assoc($result)){
        $books[]= $book;
    }
    return $books;
}

function getAllBooks($conn){


    $sql = "SELECT id, title, author, cover_image, i.price
            FROM books
            JOIN inventory i ON i.book_id = books.id 
            ORDER BY is_active, title";    

    $stmt = mysqli_prepare($conn, $sql);    

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $books = [];
    while($book = mysqli_fetch_assoc($result)){
        $books[]= $book;
    }
    return $books;
}

function searchBooksByGenre($search,$genre, $conn){
    $searchPattern = "%".$search."%";
    $sql = "SELECT books.id, title, author, cover_image, i.price
            FROM books 
            JOIN book_genres bg ON books.id = bg.book_id
            JOIN genres g ON bg.genre_id = g.id
            JOIN inventory i ON i.book_id = books.id
            WHERE g.name = ?
            AND (title LIKE ?
            OR author LIKE ?)
            ORDER BY is_active, title";    

    $stmt = mysqli_prepare($conn, $sql);    

    mysqli_stmt_bind_param($stmt, "sss", $genre, $searchPattern, $searchPattern);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $books = [];
    while($book = mysqli_fetch_assoc($result)){
        $books[]= $book;
    }
    return $books;
}

function getBooksByGenre($genre, $conn){
    $sql = "SELECT books.id, title, author, cover_image, i.price
            FROM books 
            JOIN book_genres bg ON books.id = bg.book_id
            JOIN genres g ON bg.genre_id = g.id
            JOIN inventory i ON i.book_id = books.id
            WHERE g.name = ?
            ORDER BY is_active, title";    

    $stmt = mysqli_prepare($conn, $sql);    

    mysqli_stmt_bind_param($stmt, "s", $genre);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $books = [];
    while($book = mysqli_fetch_assoc($result)){
        $books[]= $book;
    }
    return $books;
}

// lista e zhanreve per te filtruar 
function getAllGenres($conn) {
    $sql = "SELECT id, name FROM genres ORDER BY name ASC";
    $result = mysqli_query($conn, $sql);

    $genres = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $genres[] = $row;
    }
    return $genres;
}

?>
