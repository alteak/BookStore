<?php

function validateFeedback($rating, $message)
{
    if (empty($rating) || empty($message)) {
        return false;
    }

    if (!is_numeric($rating) || $rating < 1 || $rating > 5) {
        return false;
    }

    if (strlen(trim($message)) < 5) {
        return false;
    }

    return true;
}
