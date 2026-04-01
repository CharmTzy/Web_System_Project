<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

require_role('admin');

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    render_error_page(503, 'Service temporarily unavailable', service_unavailable_message());
}

$orderManagementService = new \App\Services\OrderManagementService(
    new \App\Repositories\OrderRepository($connection)
);

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$pagination = paginate_items($orderManagementService->listForAdmin(), $page, 10);

$pageTitle = 'Manage Orders';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];
$pageSkeletonVariant = 'admin-table';

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Admin panel</span>
            <h1 class="hero-section__title" style="max-width:18ch;">Manage Orders</h1>
            <p class="hero-section__copy">Monitor every seller package, delivery update, and customer destination from one workspace.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('orders/fulfillment-list', [
                'fulfillments' => $pagination['items'],
                'pagination' => $pagination,
                'viewer' => 'admin',
                'notice' => flash('admin_orders_notice'),
                'error' => flash('admin_orders_error'),
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
