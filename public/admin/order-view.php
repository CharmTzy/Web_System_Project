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

$fulfillmentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('admin_order_error', 'Session expired. Please refresh and try again.');
        header('Location: /admin/order-view.php?id=' . $fulfillmentId);
        exit;
    }

    try {
        $orderManagementService->updateForAdmin($fulfillmentId, $_POST);
        flash('admin_order_notice', 'Delivery package updated successfully.');
    } catch (\Throwable $exception) {
        report_exception($exception, 'admin.order-view');
        flash('admin_order_error', safe_exception_message($exception, 'We could not update this delivery package right now.'));
    }

    header('Location: /admin/order-view.php?id=' . $fulfillmentId);
    exit;
}

$fulfillment = $fulfillmentId !== 0 ? $orderManagementService->findForAdmin($fulfillmentId) : null;

if ($fulfillment === null) {
    flash('admin_orders_error', 'That delivery package could not be found.');
    header('Location: /admin/orders.php');
    exit;
}

$pageTitle = 'Order Package';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];
$pageSkeletonVariant = 'admin-form';

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Admin panel</span>
            <h1 class="hero-section__title" style="max-width:18ch;">Delivery Package</h1>
            <p class="hero-section__copy">Update the seller’s delivery progress, shipping details, and latest customer-facing notes.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('orders/fulfillment-detail', [
                'viewer' => 'admin',
                'fulfillment' => $fulfillment,
                'statusOptions' => $orderManagementService->adminStatusOptions(),
                'notice' => flash('admin_order_notice'),
                'error' => flash('admin_order_error'),
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
