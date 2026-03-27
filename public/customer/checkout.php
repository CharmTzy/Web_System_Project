<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

require_role('customer');

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
$addressService = new \App\Services\AddressService(
    new \App\Repositories\AddressRepository($connection)
);
$checkoutService = new \App\Services\CheckoutService(
    $cartService,
    new \App\Repositories\AddressRepository($connection),
    new \App\Repositories\OrderRepository($connection),
    new \App\Repositories\ProductRepository($connection),
);

$userId = (int) $_SESSION['user_id'];
$cartSummary = $cartService->summary();

if (!empty($cartSummary['is_empty'])) {
    flash('checkout_error', 'Add something to your cart before checkout.');
    header('Location: /cart.html');
    exit;
}

$addresses = $addressService->listForUser($userId);
$formError = null;
$selectedAddressId = (int) ($_POST['address_id'] ?? ($addresses[0]['id'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $formError = 'Session expired. Please refresh and try again.';
    } else {
        try {
            $order = $checkoutService->checkout(
                $userId,
                $selectedAddressId
            );
            flash('orders_notice', 'Your order ' . $order['order_number'] . ' has been placed successfully.');
            header('Location: /customer/orders.php');
            exit;
        } catch (\Throwable $exception) {
            report_exception($exception, 'customer.checkout');
            $formError = safe_exception_message($exception, 'We could not place your order right now.');
        }
    }
}

$pageTitle = 'Checkout';
$appName = $config['app']['name'];

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Checkout</span>
            <h1 class="hero-section__title" style="max-width:18ch;">Review your order before checkout</h1>
            <p class="hero-section__copy">Choose a delivery address and confirm your items before placing your order.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('customer/checkout-center', [
                'cart' => $cartSummary,
                'addresses' => $addresses,
                'selectedAddressId' => $selectedAddressId,
                'formError' => $formError,
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
