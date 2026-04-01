<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'customer') {
    header('Location: /login.php');
    exit;
}

$orderNumber = trim((string) ($_GET['order'] ?? ''));
$sessionId = trim((string) ($_GET['session_id'] ?? ''));

if ($orderNumber === '') {
    flash('checkout_error', 'Unable to verify this payment session. Please try checkout again.');
    header('Location: /customer/checkout.php');
    exit;
}

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    render_error_page(503, 'Service temporarily unavailable', service_unavailable_message());
}

$cartService = new \App\Services\CartService(
    new \App\Repositories\ProductRepository($connection),
    $config['app'],
    new \App\Repositories\CartRepository($connection),
);
$orderRepository = new \App\Repositories\OrderRepository($connection);
$checkoutService = new \App\Services\CheckoutService(
    $cartService,
    new \App\Repositories\AddressRepository($connection),
    $orderRepository,
    new \App\Repositories\ProductRepository($connection),
    new \App\Repositories\CartRepository($connection),
);

$userId = (int) $_SESSION['user_id'];
$order = $orderRepository->findByOrderNumberForCustomer($orderNumber, $userId);

if ($order === null) {
    flash('checkout_error', 'That order could not be found in your account.');
    header('Location: /customer/checkout.php');
    exit;
}

if ($order['status'] === 'pending' && $sessionId !== '' && !empty($config['app']['stripe_secret_key'])) {
    try {
        $session = stripe_api_request(
            'GET',
            'checkout/sessions/' . rawurlencode($sessionId),
            (string) $config['app']['stripe_secret_key']
        );

        $sessionOrder = trim((string) ($session['metadata']['order_number'] ?? ''));
        $paymentStatus = (string) ($session['payment_status'] ?? '');

        if ($sessionOrder === $orderNumber && $paymentStatus === 'paid') {
            $checkoutService->finalizePendingOrder($orderNumber);
            $order = $orderRepository->findByOrderNumberForCustomer($orderNumber, $userId) ?? $order;
        }
    } catch (\Throwable $exception) {
        report_exception($exception, 'customer.checkout-success');
    }
}

$pendingOrderMarker = (string) ($_SESSION['checkout_pending_order'] ?? '');
$pendingCartSignature = (string) ($_SESSION['checkout_pending_signature'] ?? '');
$clearedOrders = $_SESSION['checkout_cleared_orders'] ?? [];

if (!is_array($clearedOrders)) {
    $clearedOrders = [];
}

if (in_array((string) $order['status'], ['paid', 'shipped', 'delivered'], true)
    && empty($clearedOrders[$orderNumber])) {
    $cartSignature = static function (array $items): string {
        $signature = array_map(
            static fn (array $item): string => implode(':', [
                (int) ($item['product_id'] ?? 0),
                (int) ($item['seller_id'] ?? 0),
                (int) ($item['quantity'] ?? 0),
                number_format((float) ($item['unit_price'] ?? 0), 2, '.', ''),
                number_format((float) ($item['line_total'] ?? 0), 2, '.', ''),
            ]),
            $items
        );

        sort($signature);

        return implode('|', $signature);
    };

    $currentCart = $cartService->summary();
    $shouldClearCart = $sessionId !== '' || $pendingOrderMarker === $orderNumber;

    if ($shouldClearCart
        || !empty($currentCart['is_empty'])
        || ($pendingCartSignature !== '' && $cartSignature($currentCart['items']) === $pendingCartSignature)) {
        $cartService->clear();
    }

    $clearedOrders[$orderNumber] = true;
    $_SESSION['checkout_cleared_orders'] = $clearedOrders;
    unset($_SESSION['checkout_pending_order'], $_SESSION['checkout_pending_signature']);
}

$paymentsInTestMode = payments_use_test_mode($config['app']);
$pageTitle = 'Payment status';
$appName = $config['app']['name'];

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Checkout</span>
            <h1 class="hero-section__title" style="max-width:18ch;">Payment status</h1>
            <p class="hero-section__copy">
                <?= $paymentsInTestMode
                    ? 'We’re verifying your Stripe test payment and order details.'
                    : 'We’re verifying your Stripe payment and order details.' ?>
            </p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('customer/checkout-success', [
                'order' => $order,
                'sessionId' => $sessionId,
                'paymentsInTestMode' => $paymentsInTestMode,
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
