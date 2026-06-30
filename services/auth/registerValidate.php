<?php

function validateRegisterData($email, $password, $confirm)
{
    // bosh?
    if (empty($email) || empty($password) || empty($confirm)) {
        return false;
    }

    // email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    // password = confirm
    if ($password !== $confirm) {
        return false;
    }

    // minimum length
    if (strlen($password) < 6) {
        return false;
    }

    // password strength
    if (
        !preg_match('/[A-Z]/', $password) ||   // shkronjë e madhe
        !preg_match('/[a-z]/', $password) ||   // shkronjë e vogël
        !preg_match('/\d/', $password)    ||   // numër
        !preg_match('/[^A-Za-z\d]/', $password) // simbol
    ) {
        return false;
    }

    return true;
}
