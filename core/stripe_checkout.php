<?php
require_once (dirname(__DIR__) . '/vendor/autoload.php');
require_once("setting.php");

\Stripe\Stripe::setApiKey(Settings::$stripeKey);

function launchStripePayment(
    float $amount,
    string $successUrl,
    string $cancelUrl,
    string $description = "Learn4Kids Payment"
) {

    // Stripe expects cents
    $amountCents = intval(round($amount * 100));

    $session = \Stripe\Checkout\Session::create([

        'payment_method_types' => ['card'],

        'mode' => 'payment',

        'line_items' => [[

            'price_data' => [

                'currency' => 'cad',

                'product_data' => [
                    'name' => $description,
                ],

                'unit_amount' => $amountCents,

            ],

            'quantity' => 1,

        ]],

        'success_url' => $successUrl .
            '?session_id={CHECKOUT_SESSION_ID}',

        'cancel_url' => $cancelUrl,

    ]);

    header("Location: " . $session->url);
    exit;
}