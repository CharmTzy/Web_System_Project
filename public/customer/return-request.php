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
$returnRequestService = new \App\Services\OrderReturnRequestService(
    new \App\Repositories\OrderReturnRequestRepository($connection),
    $orderRepository
);

$customerId = (int) $_SESSION['user_id'];
$orderId = (int) ($_POST['order_id'] ?? filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT) ?: 0);
$sellerId = (int) ($_POST['seller_id'] ?? filter_input(INPUT_GET, 'seller_id', FILTER_VALIDATE_INT) ?: 0);
$package = ($orderId > 0 && $sellerId > 0) ? $orderRepository->findPackageForCustomer($orderId, $customerId, $sellerId) : null;

if ($package === null) {
    flash('orders_error', 'That delivered package could not be found in your account.');
    header('Location: /customer/orders.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('return_request_error', 'Session expired. Please refresh and try again.');
        header('Location: /customer/return-request.php?order_id=' . $orderId . '&seller_id=' . $sellerId);
        exit;
    }

    try {
        $returnRequestService->createForCustomer($customerId, $_POST);
        flash('orders_notice', 'Your return or refund request has been sent to the seller for review.');
        header('Location: /customer/orders.php');
        exit;
    } catch (\Throwable $exception) {
        report_exception($exception, 'customer.return-request');
        flash('return_request_error', safe_exception_message($exception, 'We could not submit your request right now.'));
        header('Location: /customer/return-request.php?order_id=' . $orderId . '&seller_id=' . $sellerId);
        exit;
    }
}

$latestRequest = $returnRequestService->findLatestForCustomerPackage($customerId, $orderId, $sellerId);
$hasActiveRequest = is_array($latestRequest) && in_array((string) ($latestRequest['status'] ?? ''), ['pending', 'approved', 'received'], true);

$pageTitle = 'Return or Refund';
$appName = $config['app']['name'];
$cartSummary = $cartService->summary();
$pageSkeletonVariant = 'panel';

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Orders</span>
            <h1 class="hero-section__title" style="max-width:18ch;">Return or Refund</h1>
            <p class="hero-section__copy">Share what went wrong with this delivery package and the seller will review your request.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('customer/return-request-form', [
                'package' => $package,
                'latestRequest' => $latestRequest,
                'requestTypeOptions' => $returnRequestService->requestTypeOptions(),
                'reasonOptions' => $returnRequestService->reasonOptions(),
                'featureAvailable' => $returnRequestService->isAvailable(),
                'hasActiveRequest' => $hasActiveRequest,
                'notice' => flash('return_request_notice'),
                'error' => flash('return_request_error'),
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
