<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'customer') {
    header('Location: /login.php');
    exit;
}

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

$pageTitle = 'My Coupons';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];
$pageSkeletonVariant = 'panel';
$robotsMeta = 'noindex, nofollow, noarchive';

try {
    if (!$connection) {
        throw new RuntimeException('Database connection required.');
    }

    $couponData = (new \App\Services\CouponService(
        new \App\Repositories\CouponRepository($connection),
        'mysql'
    ))->browse();
} catch (Throwable) {
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
