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
$couponRepository = new \App\Repositories\CouponRepository($connection);
$checkoutCouponService = new \App\Services\CheckoutCouponService($couponRepository);
$addressService = new \App\Services\AddressService(
    new \App\Repositories\AddressRepository($connection)
);

$userId = (int) $_SESSION['user_id'];
$cartSummary = $cartService->summary();
$selectedCouponCode = strtoupper(trim((string) ($_GET['coupon_code'] ?? '')));
$availableCoupons = $checkoutCouponService->checkoutOptions($cartSummary);
$appliedCoupon = null;

if (!empty($cartSummary['is_empty'])) {
    flash('checkout_error', 'Add something to your cart before checkout.');
    header('Location: /cart.html');
    exit;
}

$addresses = $addressService->listForUser($userId);
$formError = flash('checkout_error');
$selectedAddressId = (int) ($_GET['address_id'] ?? ($addresses[0]['id'] ?? 0));

if ($selectedCouponCode !== '') {
    try {
        $couponResult = $checkoutCouponService->applyCoupon($cartSummary, $selectedCouponCode);
        $cartSummary = $couponResult['summary'];
        $appliedCoupon = $couponResult['coupon'];
    } catch (\Throwable $exception) {
        $formError = safe_exception_message($exception, 'We could not apply that coupon right now.');
        $selectedCouponCode = '';
    }
}

$paymentsInTestMode = payments_use_test_mode($config['app']);

$pageTitle = 'Checkout';
$appName = $config['app']['name'];

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Checkout</span>
            <h1 class="hero-section__title" style="max-width:18ch;">Review your order before payment</h1>
            <p class="hero-section__copy">
                <?= $paymentsInTestMode
                    ? 'Choose your delivery address, then continue to Stripe test checkout to verify the payment flow safely.'
                    : 'Choose your delivery address, then continue to Stripe to complete payment securely.' ?>
            </p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('customer/checkout-center', [
                'cart' => $cartSummary,
                'addresses' => $addresses,
                'selectedAddressId' => $selectedAddressId,
                'formError' => $formError,
                'paymentsInTestMode' => $paymentsInTestMode,
                'availableCoupons' => $availableCoupons,
                'selectedCouponCode' => $selectedCouponCode,
                'appliedCoupon' => $appliedCoupon,
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
