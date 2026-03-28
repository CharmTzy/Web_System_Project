<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'customer') {
    header('Location: /login.php?redirect=' . rawurlencode('/customer/checkout.php'));
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    flash('checkout_error', 'Session expired. Please refresh and try again.');
    header('Location: /customer/checkout.php');
    exit;
}

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    flash('checkout_error', service_unavailable_message());
    header('Location: /customer/checkout.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$selectedAddressId = (int) ($_POST['address_id'] ?? 0);

if ($selectedAddressId < 1) {
    flash('checkout_error', 'Please select a shipping address.');
    header('Location: /customer/checkout.php');
    exit;
}

$cartService = new \App\Services\CartService(
    new \App\Repositories\ProductRepository($connection),
    $config['app'],
    new \App\Repositories\CartRepository($connection),
);
$checkoutService = new \App\Services\CheckoutService(
    $cartService,
    new \App\Repositories\AddressRepository($connection),
    new \App\Repositories\PaymentCardRepository($connection),
    new \App\Repositories\OrderRepository($connection),
    new \App\Repositories\ProductRepository($connection),
    new \App\Repositories\CartRepository($connection),
);

try {
    $pendingOrder = $checkoutService->createPendingOrder($userId, $selectedAddressId);

    $lineItems = [];
    foreach ($pendingOrder['items'] as $item) {
        $lineItems[] = [
            'price_data' => [
                'currency' => (string) ($config['app']['currency'] ?? 'sgd'),
                'product_data' => [
                    'name' => (string) $item['product_name'],
                ],
                'unit_amount' => (int) round(((float) $item['unit_price']) * 100),
            ],
            'quantity' => (int) $item['quantity'],
        ];
    }

    if ((float) $pendingOrder['shipping_fee'] > 0) {
        $lineItems[] = [
            'price_data' => [
                'currency' => (string) ($config['app']['currency'] ?? 'sgd'),
                'product_data' => [
                    'name' => 'Shipping fee',
                ],
                'unit_amount' => (int) round(((float) $pendingOrder['shipping_fee']) * 100),
            ],
            'quantity' => 1,
        ];
    }

    $appUrl = (string) ($config['app']['url'] ?? 'http://localhost:8000');
    $successUrl = $appUrl
        . '/customer/checkout-success.php?session_id={CHECKOUT_SESSION_ID}&order='
        . rawurlencode((string) $pendingOrder['order_number']);

    $session = stripe_api_request('POST', 'checkout/sessions', (string) $config['app']['stripe_secret_key'], [
        'mode' => 'payment',
        'success_url' => $successUrl,
        'cancel_url' => $appUrl . '/customer/checkout.php?address_id=' . $selectedAddressId,
        'line_items' => $lineItems,
        'metadata' => [
            'order_number' => (string) $pendingOrder['order_number'],
            'user_id' => (string) $userId,
        ],
    ]);

    $checkoutUrl = (string) ($session['url'] ?? '');

    if ($checkoutUrl === '') {
        throw new RuntimeException('Unable to start Stripe checkout. Please try again.');
    }

    header('Location: ' . $checkoutUrl, true, 303);
    exit;
} catch (\Throwable $exception) {
    report_exception($exception, 'api.stripe-checkout');
    flash('checkout_error', safe_exception_message($exception, 'Unable to start Stripe checkout right now.'));
    header('Location: /customer/checkout.php?address_id=' . $selectedAddressId);
    exit;
}
