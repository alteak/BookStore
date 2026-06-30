<?php

// Prevent any output before JSON
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any stray output
ob_start();

try {
    require_once __DIR__ . '/../../../database/databaseConnection.php';
    require_once __DIR__ . '/../../../config.php';
    require_once __DIR__ . '/../../../services/StripeAPI/stripePaymentAPIHandler.php';
    
    // Clear any accidental output
    ob_end_clean();
    
    // Now set header and start fresh output
    header('Content-Type: application/json');
    
    $action = $_GET['action'] ?? '';

    if ($action === 'create_intent') {
        // Get POST data
        $amount = floatval($_POST['amount'] ?? 0);
        $currency = $_POST['currency'] ?? 'usd';
        $description = $_POST['description'] ?? 'BookStore Order';
        
        // Convert to cents
        $amountCents = intval($amount * 100);
        
        if ($amountCents <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'error' => 'Invalid amount: ' . $amount,
                'debug' => [
                    'raw_amount' => $_POST['amount'] ?? 'missing',
                    'calculated_cents' => $amountCents
                ]
            ]);
            exit;
        }
        
        $stripeHandler = new StripePaymentHandler(STRIPE_SECRET_KEY);
        $result = $stripeHandler->createPaymentIntent(
            $amountCents,
            $currency,
            $description
        );
        
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action: ' . $action]);
    }
} catch (Exception $e) {
    // Clear any buffered output
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

exit;
