<?php 
// Note: sessionHandler and database connection should be included before this file

require_once __DIR__ . '/../email/emailService.php';

// merr te dhenat nga shporta dhe kontrollon sasine e kerkuar ne krahasim me stock
// shtimi ne shporte i sasive mbi nr stock nuk lejohet, por mund te vije si pasoje e ndryshimeve te stokut ne kohe reale (data concurreny pyet profin si mund te shmanget sa me shume)

function getCart($email, $conn){
    $sql = "SELECT c.book_id, b.title, i.price, c.quantity, i.stock
            FROM cart c
            INNER JOIN books b ON b.id = c.book_id
            LEFT JOIN inventory i ON i.book_id = b.id
            WHERE c.user_email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $cartItems = [];
    while ($row = $result->fetch_assoc()) {
        $cartItems[] = $row;
    }
    foreach($cartItems as &$item){
      if($item['quantity'] > $item['stock']){
        return false;
      }
    }
    foreach($cartItems as &$item){
      $item['total_price'] = $item['price'] * $item['quantity'];
    }
    return $cartItems;
}  


// jane ndare ne 3 funksione per arsye te shfaqjes se te dhenave gjate checkoutit (edhe nqs totali do ishte i njejte, perdoruesve u pelqen te shohin nje subtotal qe zbritet)

function calculateSubtotal($cartItems){
    if(!is_array($cartItems)){
        return 0;
    }
    $subtotal = 0;
    foreach($cartItems as $item){
        $subtotal += $item['total_price'];
    }
    return $subtotal;
}


function getDiscountAmount($code, $subtotal, $conn){
 $sql = "SELECT type, value FROM discounts WHERE code = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
        $discount = $result->fetch_assoc();
        if($discount['type'] === 'PERCENT'){
            return ($subtotal * $discount['value']) / 100;
        } elseif($discount['type'] === 'FIXED'){
            return $discount['value'];
        }
    }
    return 0;
}


function getTotal($subtotal, $shipping, $discountAmount){
    return $subtotal + $shipping - $discountAmount;
}


//krijon nje hyrje ne bazen e te dhenave, e perditeson pas konfirmimit te pageses

function createOrder($userEmail, $cartItems, $code, $conn){
    $orderNumber = uniqid('ORD-');
    $subTotal = calculateSubtotal($cartItems);
    $totalAmount = getTotal($subTotal, 0, getDiscountAmount($code, $subTotal, $conn));
    $sql = "INSERT INTO orders (id, user_email, total_amount, status, created_at) 
            VALUES (?, ?, ?, 'PENDING', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssd", $orderNumber, $userEmail, $totalAmount);
    if($stmt->execute()){
        $orderId = $stmt->insert_id;
        $itemSql = "INSERT INTO order_items (order_id, book_id, quantity, price) 
                    VALUES (?, ?, ?, ?)";
        $itemStmt = $conn->prepare($itemSql);
        foreach($cartItems as $item){
            $itemStmt->bind_param("iiid", $orderId, $item['book_id'], $item['quantity'], $item['price']);
            $itemStmt->execute();
        }
        return ['order_id' => $orderId, 'order_number' => $orderNumber, 'total_amount' => $totalAmount];
    } else {
        return null;
    }
}


function displayOrderSummary($orderDetails){
    // display order summary
    echo "Order Number: " . htmlspecialchars($orderDetails['order_number']) . "<br>";
    echo "Total Amount: " . number_format($orderDetails['total_amount'], 2) . " €<br>";
}


// nqs pagesa esht e sukseshme, zbras shporten

function clearCart($userEmail, $conn){
    $sql = "DELETE FROM cart WHERE user_email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
}


// si forme 'rezervimi' per stockun gjate momentit te checkoutit, per te shmangur porosite mbi sasine qe nevojitet gjates pritjes te konfirmimit te pageses

function holdStock($cartItems, $conn){
    foreach($cartItems as $item){
        $sql = "UPDATE inventory SET stock = stock - ? WHERE book_id = ? AND stock >= ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $item['quantity'], $item['book_id'], $item['quantity']);
        $stmt->execute();
        if($stmt->affected_rows === 0){
            return false; // Not enough stock
        }
    }
    return true;
}


// Ndryshon statusin e porosise ne baze te rezultatit te pageses

function updateOrderStatus($orderId, $status, $conn){
    $sql = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $orderId);
    $stmt->execute();
}


function processPayment($email, $orderId, $orderNumber, $amount, $paymentIntentId, $shippingData, $conn){
    // Call payment API with the payment intent ID
    $paymentResult = callPaymentAPI($amount, $paymentIntentId);
    
    // if me te dhena jo NULL jane gjithmone true, essentially kontrollon ekzistencen 
    if($paymentResult){
        updateOrderWithShippingData($orderId, $shippingData, $conn);
        
        $customerName = $shippingData['name'] ?? 'Unknown';
        logPayment($orderId, $amount, 'SUCCESS', $customerName, $conn);
        updateOrderStatus($orderId, 'PAID', $conn);
        sendInvoiceEmail($email, $orderNumber);
        return true;
    } else {
        $customerName = $shippingData['name'] ?? 'Unknown';
        logPayment($orderId, $amount, 'FAILED', $customerName, $conn);
        updateOrderStatus($orderId, 'PENDING', $conn);
        return false;
    }
}
   

// a eshte me mire qe metodat te shkruhen sipas rradhes se implementimit apo therritjes?

function callPaymentAPI($amount, $paymentIntentId){
    // Call Stripe API to confirm payment
    require_once __DIR__ . '/../StripeAPI/stripePaymentAPIHandler.php';
    
    $stripeHandler = new StripePaymentHandler(STRIPE_SECRET_KEY);
    $result = $stripeHandler->confirmPayment($paymentIntentId);
    
    return $result['success'];
}


// perdor transaksionet ne database
// Ne cdo hap ka mundesine kthimit te gjendje se meparshme ne rast deshtimi
// metoda kryesore, ketu therret te gjitha kontrollet 

function checkout($userEmail, $conn, $discountCode = '', $paymentIntentId = '', $shippingData = []){
    $conn->begin_transaction();
    
    $cartItems = getCart($userEmail, $conn);
    if($cartItems === false){
        $conn->rollback();
        return ['success' => false, 'message' => 'Not enough stock for some items in cart.'];
    }

    if(!holdStock($cartItems, $conn)){
        $conn->rollback();
        return ['success' => false, 'message' => 'Not enough stock for some items in cart.'];
    }

    $orderDetails = createOrder($userEmail, $cartItems, $discountCode, $conn);
    if($orderDetails === null){
        $conn->rollback();
        return ['success' => false, 'message' => 'Failed to create order.'];
    }

    if(!processPayment($userEmail, $orderDetails['order_id'], $orderDetails['order_number'], $orderDetails['total_amount'], $paymentIntentId, $shippingData, $conn)){
        $conn->rollback();
        return ['success' => false, 'message' => 'Payment failed.'];
    }

    clearCart($userEmail, $conn);
    $conn->commit();
    return ['success' => true, 'order_details' => $orderDetails];
}


// mban nje log per payment attempt either way

function logPayment($orderId, $amount, $status, $customerName, $conn){
    $sql = "INSERT INTO payments (order_id, amount, status, customer_name, created_at) 
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idss", $orderId, $amount, $status, $customerName);
    $stmt->execute();
}


// nqs porosia konfirmohet, ndrysho statusin, shto te dhenat e klientit per dergmin e porosise etj

function updateOrderWithShippingData($orderId, $shippingData, $conn){
    if (empty($shippingData)) {
        return true;
    }
    
    $name = $shippingData['name'] ?? null;
    $address = $shippingData['address'] ?? null;
    $city = $shippingData['city'] ?? null;
    $phone = $shippingData['phone'] ?? null;
    
    $sql = "UPDATE orders SET customer_name = ?, shipping_address = ?, city = ?, phone = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $name, $address, $city, $phone, $orderId);
    return $stmt->execute();
}


