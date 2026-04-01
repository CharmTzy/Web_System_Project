<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

require_role('seller');

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    render_error_page(503, 'Service temporarily unavailable', service_unavailable_message());
}

$userService = new \App\Services\UserService(
    new \App\Repositories\UserRepository($connection)
);
$productService = new \App\Services\ProductManagementService(
    new \App\Repositories\ProductRepository($connection)
);
$orderManagementService = new \App\Services\OrderManagementService(
    new \App\Repositories\OrderRepository($connection)
);

$profile = $userService->getProfile((int) $_SESSION['user_id']);
$products = $productService->listSellerProducts((int) $_SESSION['user_id']);
$orderStats = [
    'total' => 0,
    'awaiting_action' => 0,
    'in_transit' => 0,
    'delivered' => 0,
    'cancelled' => 0,
];

try {
    $fulfillments = $orderManagementService->listForSeller((int) $_SESSION['user_id']);
    $orderStats = $orderManagementService->summarize($fulfillments);
} catch (\Throwable $exception) {
    report_exception($exception, 'seller.dashboard.orders');
}
$stats = [
    'total_products' => count($products),
    'active_products' => count(array_filter($products, static fn (array $product): bool => $product['is_active'])),
    'out_of_stock_products' => count(array_filter($products, static fn (array $product): bool => (int) $product['stock_quantity'] === 0)),
];

$pageTitle = 'Seller Dashboard';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];
$pageSkeletonVariant = 'admin-dashboard';

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Seller panel</span>
            <h1 class="hero-section__title" style="max-width:20ch;">Seller Dashboard</h1>
            <p class="hero-section__copy">Manage your store profile and view your seller information.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('seller/dashboard', ['profile' => $profile, 'stats' => $stats, 'orderStats' => $orderStats]) ?>
            <div class="d-flex gap-3 flex-wrap">
                <a class="btn btn-brand" href="/seller/orders.php">Manage orders</a>
                <a class="btn btn-brand" href="/seller/products.php">Manage products</a>
                <a class="btn btn-brand-outline" href="/seller/product-edit.php">Add new product</a>
                <a class="btn btn-brand-outline" href="/seller/chat.php">Open chat inbox</a>
                <a class="btn btn-brand-outline" href="/seller/store-profile.php">Edit store profile</a>
            </div>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
