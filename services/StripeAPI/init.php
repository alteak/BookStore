<?php

// Stripe PHP SDK Autoloader
spl_autoload_register(function ($class) {
    if (strpos($class, 'Stripe\\') === 0) {
        $relative_class = substr($class, 7); // Remove 'Stripe\' prefix
        $file = __DIR__ . '/lib/' . str_replace('\\', '/', $relative_class) . '.php';
        
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    return false;
});

// Load the main Stripe class
require_once __DIR__ . '/lib/Stripe.php';
