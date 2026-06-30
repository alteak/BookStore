<?php

require_once __DIR__ . '/init.php';
require_once dirname(__DIR__, 2) . '/config.php';

    // lehtesisht e gatshme per tu implementuar si API, marr merita vetem per logjiken e biznesit

    // per pjesen kryesore te projektit kemi perdorur nocion te thjeshte prograndimi procedural
    // per stripe perdorim OOP, SDK e gatshme, metodat e implementuara per objektet, sistem financiar -> siguria nevojitet e larte
    // metoda publike, te dhena private

class StripePaymentHandler {
    private $stripeSecretKey;
    
    public function __construct($secretKey) {
        $this->stripeSecretKey = $secretKey;
        \Stripe\Stripe::setApiKey($this->stripeSecretKey);
        
        // Certifikatat e verifikimit, nuk punon pa keto
        \Stripe\Stripe::setCABundlePath(__DIR__ . '/data/ca-certificates.crt');
    }
    
    /**
     * Create a Payment Intent for checkout
     * @param int $amount - Amount in cents (e.g., 1000 = $10.00)
     * @param string $currency - Currency code (e.g., 'eur')
     * @param string $description - Description of the payment
     * @return array - Contains client_secret and payment_intent_id
     */


    // kthen objekt te tipit PaymentIntent
    // perdorim exception handling per te shmangur crashes gjate gabimeve ne runtime -> shfaq te dhena te sakta mbi gabimin
    public function createPaymentIntent($amount, $currency = 'eur', $description = '') {
        try {
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $amount,
                'currency' => $currency,
                'description' => $description,
                'payment_method_types' => ['card'],
            ]);
            
            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Confirm payment after client-side confirmation
     * @param string $paymentIntentId - The Payment Intent ID
     * @return array - Success status and payment details
     */

    public function confirmPayment($paymentIntentId) {
        try {
            $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
            
            if ($paymentIntent->status === 'succeeded') {
                return [
                    'success' => true,
                    'payment_intent_id' => $paymentIntent->id,
                    'status' => $paymentIntent->status,
                    'amount' => $paymentIntent->amount,
                ];
            } else if ($paymentIntent->status === 'requires_action') {
                return [
                    'success' => false,
                    'error' => 'Payment requires additional action',
                    'status' => $paymentIntent->status,
                ];
            } else if ($paymentIntent->status === 'processing') {
                return [
                    'success' => false,
                    'error' => 'Payment is processing',
                    'status' => $paymentIntent->status,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Payment failed',
                    'status' => $paymentIntent->status,
                ];
            }
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

?>
