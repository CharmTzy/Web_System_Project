<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

$payload = file_get_contents('php://input');
$signature = (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');

if (!is_string($payload) || $payload === '') {
    http_response_code(400);
    echo 'Missing payload.';
    exit;
}

$webhookSecret = (string) ($config['app']['stripe_webhook_secret'] ?? '');
$tolerance = (int) ($config['app']['stripe_webhook_tolerance'] ?? 300);

if (!stripe_verify_webhook_signature($payload, $signature, $webhookSecret, $tolerance)) {
    http_response_code(400);
    echo 'Invalid signature.';
    exit;
}

$event = json_decode($payload, true);

if (!is_array($event)) {
    http_response_code(400);
    echo 'Invalid payload.';
    exit;
}

try {
    $type = (string) ($event['type'] ?? '');

    if ($type === 'checkout.session.completed') {
        $session = is_array($event['data']['object'] ?? null) ? $event['data']['object'] : [];
        $paymentStatus = (string) ($session['payment_status'] ?? '');

        if ($paymentStatus === 'paid') {
            $metadata = is_array($session['metadata'] ?? null) ? $session['metadata'] : [];
            $orderNumber = trim((string) ($metadata['order_number'] ?? ''));

            if ($orderNumber !== '') {
                $database = new \App\Support\Database($config['database']);
                $connection = $database->connection();

                if (!$connection) {
                    throw new RuntimeException('Database unavailable during webhook handling.');
                }

                $checkoutService = new \App\Services\CheckoutService(
                    new \App\Services\CartService(
                        new \App\Repositories\ProductRepository($connection),
                        $config['app'],
                        new \App\Repositories\CartRepository($connection),
                    ),
                    new \App\Repositories\AddressRepository($connection),
                    new \App\Repositories\OrderRepository($connection),
                    new \App\Repositories\ProductRepository($connection),
                    new \App\Repositories\CartRepository($connection),
                );

                $checkoutService->finalizePendingOrder($orderNumber);
            }
        }
    }

    http_response_code(200);
    echo 'ok';
} catch (\Throwable $exception) {
    report_exception($exception, 'api.stripe-webhook');
    http_response_code(500);
    echo 'Webhook handler error.';
}
