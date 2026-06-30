<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "pw_ecommerce";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die(
        "Lidhja me databazen deshtoi: " .
        mysqli_connect_errno() . " - " .
        mysqli_connect_error()
    );
}
