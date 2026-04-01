<?php

declare(strict_types=1);

header('Content-Type: application/json');

function cart_login_url(): string
{
    return '/login.php?redirect=' . rawurlencode('/cart.html') . '&cart_notice=full-cart';
}

function guest_full_cart_prompt(): array
{
    return [
        'title' => 'The full cart can only be accessed after login.',
        'copy' => 'Sign in to open your full cart. Any items you added as a guest will be kept and added to your account cart after you sign in.',
        'login_url' => cart_login_url(),
        'cta_label' => 'Go to sign in',
    ];
}

function guest_drawer_cart(array $summary): array
{
    $summary['review_url'] = cart_login_url();
    $summary['requires_login_for_full_cart'] = true;

    return $summary;
}

try {
    $config = require dirname(__DIR__, 2) . '/bootstrap.php';
    $database = new \App\Support\Database($config['database']);
    $connection = $database->connection();

    if (!$connection) {
        throw new RuntimeException('Database connection required.');
    }

    $productRepository = new \App\Repositories\ProductRepository($connection);
    $cartRepository = new \App\Repositories\CartRepository($connection);
    $cartService = new \App\Services\CartService($productRepository, $config['app'], $cartRepository);

    if (!empty($_SESSION['user_id'])
        && (string) ($_SESSION['user_role'] ?? '') === 'customer'
        && !empty($config['app']['stripe_secret_key'])) {
        $checkoutService = new \App\Services\CheckoutService(
            $cartService,
            new \App\Repositories\AddressRepository($connection),
            new \App\Repositories\OrderRepository($connection),
            $productRepository,
            $cartRepository,
        );

        try {
            $checkoutService->reconcilePendingOrdersForCustomer(
                (int) $_SESSION['user_id'],
                (string) $config['app']['stripe_secret_key']
            );
        } catch (\Throwable $exception) {
            report_exception($exception, 'api.cart.reconcile');
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $summary = $cartService->summary();

        if (empty($_SESSION['user_id'])) {
            respond([
                'ok' => true,
                'login_url' => cart_login_url(),
                'count' => $summary['total_items'],
                'total_items' => $summary['total_items'],
                'subtotal_formatted' => $summary['subtotal_formatted'],
                'grand_total_formatted' => $summary['grand_total_formatted'],
                'drawer_html' => render('partials/cart-panel', ['cart' => guest_drawer_cart($summary)]),
                'cart_html' => render('partials/cart-signin-prompt', guest_full_cart_prompt()),
            ]);
        }

        respond([
            'ok' => true,
            'count' => $summary['total_items'],
            'total_items' => $summary['total_items'],
            'subtotal_formatted' => $summary['subtotal_formatted'],
            'grand_total_formatted' => $summary['grand_total_formatted'],
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

    $result = match ($action) {
        'add' => $cartService->add($productId, $quantity),
        'update' => $cartService->update($productId, $quantity),
        'remove' => $cartService->remove($productId),
        default => throw new InvalidArgumentException('Unknown cart action.'),
    };

    $summary = $result['summary'];
    $isGuest = empty($_SESSION['user_id']);

    respond([
        'ok' => true,
        'message' => $result['message'],
        'count' => $summary['total_items'],
        'total_items' => $summary['total_items'],
        'subtotal' => $summary['subtotal_formatted'],
        'subtotal_formatted' => $summary['subtotal_formatted'],
        'grand_total_formatted' => $summary['grand_total_formatted'],
        'drawer_html' => render('partials/cart-panel', ['cart' => $isGuest ? guest_drawer_cart($summary) : $summary]),
        'cart_html' => $isGuest
            ? render('partials/cart-signin-prompt', guest_full_cart_prompt())
            : render('partials/cart-table', ['cart' => $summary]),
    ]);
} catch (\InvalidArgumentException | \RuntimeException $exception) {
    report_exception($exception, 'api.cart.expected');
    respond([
        'ok' => false,
        'message' => $exception->getMessage(),
    ], 422);
} catch (Throwable $exception) {
    report_exception($exception, 'api.cart.unexpected');
    respond([
        'ok' => false,
        'message' => service_unavailable_message(),
    ], 500);
}

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}
