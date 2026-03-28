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
$orderRepository = new \App\Repositories\OrderRepository($connection);
$reviewRepository = new \App\Repositories\ReviewRepository($connection);
$returnRequestService = new \App\Services\OrderReturnRequestService(
    new \App\Repositories\OrderReturnRequestRepository($connection),
    $orderRepository
);

$orders = $orderRepository->listByCustomer((int) $_SESSION['user_id']);
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$pagination = paginate_items($orders, $page, 6);
$productIds = [];

foreach ($pagination['items'] as $order) {
    foreach ($order['items'] as $item) {
        $productIds[] = (int) $item['product_id'];
    }
}

$reviewedProductIds = $reviewRepository->reviewedProductIdsForUser((int) $_SESSION['user_id'], $productIds);
$returnRequestsByPackage = $returnRequestService->requestsByPackageForCustomer((int) $_SESSION['user_id']);

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
                'orders' => $pagination['items'],
                'pagination' => $pagination,
                'reviewedProductIds' => $reviewedProductIds,
                'returnRequestsByPackage' => $returnRequestsByPackage,
                'returnRequestsEnabled' => $returnRequestService->isAvailable(),
                'notice' => flash('orders_notice'),
                'error' => flash('orders_error') ?: flash('checkout_error'),
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
