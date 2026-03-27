<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'customer') {
    header('Location: /login.php');
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
$reviewRepository = new \App\Repositories\ReviewRepository($connection);

$orders = $orderRepository->listByCustomer((int) $_SESSION['user_id']);
$productIds = [];

foreach ($orders as $order) {
    foreach ($order['items'] as $item) {
        $productIds[] = (int) $item['product_id'];
    }
}

$reviewedProductIds = $reviewRepository->reviewedProductIdsForUser((int) $_SESSION['user_id'], $productIds);

$pageTitle = 'My Orders';
$appName = $config['app']['name'];
$cartSummary = $cartService->summary();

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Order history</span>
            <h1 class="hero-section__title" style="max-width:18ch;">My Orders</h1>
            <p class="hero-section__copy">Review your recent orders and jump back to items you want to rate.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('customer/order-history', [
                'orders' => $orders,
                'reviewedProductIds' => $reviewedProductIds,
                'notice' => flash('orders_notice'),
                'error' => flash('checkout_error'),
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
