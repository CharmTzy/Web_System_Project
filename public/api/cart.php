<?php

declare(strict_types=1);

header('Content-Type: application/json');

$config = require dirname(__DIR__, 2) . '/bootstrap.php';
$services = \App\Support\AppFactory::storefront($config);
$cartService = $services['cart'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $summary = $cartService->summary();

    respond([
        'ok' => true,
        'count' => $summary['total_items'],
        'drawer_html' => render('partials/cart-panel', ['cart' => $summary]),
        'cart_html' => render('partials/cart-table', ['cart' => $summary]),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond([
        'ok' => false,
        'message' => 'Method not allowed.',
    ], 405);
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    respond([
        'ok' => false,
        'message' => 'Your session expired. Refresh the page and try again.',
    ], 419);
}

$action = (string) ($_POST['action'] ?? '');
$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT) ?: 0;
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
$quantity = $quantity === false ? 1 : (int) $quantity;

try {
    $result = match ($action) {
        'add' => $cartService->add($productId, $quantity),
        'update' => $cartService->update($productId, $quantity),
        'remove' => $cartService->remove($productId),
        default => throw new InvalidArgumentException('Unknown cart action.'),
    };

    $summary = $result['summary'];

    respond([
        'ok' => true,
        'message' => $result['message'],
        'count' => $summary['total_items'],
        'subtotal' => $summary['subtotal_formatted'],
        'drawer_html' => render('partials/cart-panel', ['cart' => $summary]),
        'cart_html' => render('partials/cart-table', ['cart' => $summary]),
    ]);
} catch (Throwable $exception) {
    respond([
        'ok' => false,
        'message' => $exception->getMessage(),
    ], 422);
}

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}
