<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

require_role('customer');

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

$pageTitle = 'My Coupons';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];
$pageSkeletonVariant = 'coupons';
$robotsMeta = 'noindex, nofollow, noarchive';

try {
    if (!$connection) {
        throw new RuntimeException('Database connection required.');
    }

    $productRepository = new \App\Repositories\ProductRepository($connection);
    $cartRepository = new \App\Repositories\CartRepository($connection);
    $orderRepository = new \App\Repositories\OrderRepository($connection);
    $checkoutService = new \App\Services\CheckoutService(
        new \App\Services\CartService(
            $productRepository,
            $config['app'],
            $cartRepository,
        ),
        new \App\Repositories\AddressRepository($connection),
        $orderRepository,
        $productRepository,
        $cartRepository,
    );

    if (!empty($config['app']['stripe_secret_key'])) {
        $checkoutService->reconcilePendingOrdersForCustomer((int) $_SESSION['user_id'], (string) $config['app']['stripe_secret_key']);
    }

    $couponData = (new \App\Services\CouponService(
        new \App\Repositories\CouponRepository($connection),
        'mysql'
    ))->browse((int) $_SESSION['user_id']);
} catch (Throwable $exception) {
    report_exception($exception, 'customer.coupons');
    $couponData = [
        'featured' => [],
        'limited_time' => [],
        'free_shipping' => [],
        'shop_coupons' => [],
        'stats' => [
            'available_count' => 0,
            'ending_soon_count' => 0,
            'shipping_count' => 0,
        ],
        'source' => 'unavailable',
    ];
}

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <?= render('customer/coupon-center', $couponData) ?>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
