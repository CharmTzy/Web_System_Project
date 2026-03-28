<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

require_role('seller');

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    render_error_page(503, 'Service temporarily unavailable', service_unavailable_message());
}

$orderManagementService = new \App\Services\OrderManagementService(
    new \App\Repositories\OrderRepository($connection)
);

$sellerId = (int) $_SESSION['user_id'];
$fulfillmentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('seller_order_error', 'Session expired. Please refresh and try again.');
        header('Location: /seller/order-view.php?id=' . $fulfillmentId);
        exit;
    }

    try {
        if (isset($_POST['quick_status'])) {
            $existingFulfillment = $orderManagementService->findForSeller($fulfillmentId, $sellerId);

            if ($existingFulfillment !== null) {
                $_POST['status'] = (string) $_POST['quick_status'];
                $_POST['courier_name'] = (string) ($existingFulfillment['courier_name'] ?? '');
                $_POST['tracking_number'] = (string) ($existingFulfillment['tracking_number'] ?? '');
                $_POST['estimated_delivery_date'] = (string) ($existingFulfillment['estimated_delivery_date'] ?? '');
                $_POST['status_note'] = (string) ($existingFulfillment['status_note'] ?? '');
            }
        }

        $orderManagementService->updateForSeller($fulfillmentId, $sellerId, $_POST);
        flash('seller_order_notice', 'Delivery package updated successfully.');
    } catch (\Throwable $exception) {
        report_exception($exception, 'seller.order-view');
        flash('seller_order_error', safe_exception_message($exception, 'We could not update this delivery package right now.'));
    }

    header('Location: /seller/order-view.php?id=' . $fulfillmentId);
    exit;
}

$fulfillment = $fulfillmentId !== 0 ? $orderManagementService->findForSeller($fulfillmentId, $sellerId) : null;

if ($fulfillment === null) {
    flash('seller_orders_error', 'That delivery package could not be found in your store.');
    header('Location: /seller/orders.php');
    exit;
}

$pageTitle = 'Delivery Package';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];
$pageSkeletonVariant = 'admin-form';

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Seller panel</span>
            <h1 class="hero-section__title" style="max-width:18ch;">Delivery Package</h1>
            <p class="hero-section__copy">Update courier details, tracking, and progress for this customer delivery.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('orders/fulfillment-detail', [
                'viewer' => 'seller',
                'fulfillment' => $fulfillment,
                'statusOptions' => $orderManagementService->sellerStatusOptions(),
                'quickActions' => $orderManagementService->sellerQuickActions($fulfillment),
                'notice' => flash('seller_order_notice'),
                'error' => flash('seller_order_error'),
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
