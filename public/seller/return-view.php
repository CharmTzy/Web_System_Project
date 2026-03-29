<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

require_role('seller');

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    render_error_page(503, 'Service temporarily unavailable', service_unavailable_message());
}

$orderRepository = new \App\Repositories\OrderRepository($connection);
$returnRequestService = new \App\Services\OrderReturnRequestService(
    new \App\Repositories\OrderReturnRequestRepository($connection),
    $orderRepository
);

$sellerId = (int) $_SESSION['user_id'];
$requestId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('seller_return_error', 'Session expired. Please refresh and try again.');
        header('Location: /seller/return-view.php?id=' . $requestId);
        exit;
    }

    try {
        $returnRequestService->updateForSeller($requestId, $sellerId, $_POST);
        flash('seller_return_notice', 'Return request updated successfully.');
    } catch (\Throwable $exception) {
        report_exception($exception, 'seller.return-view');
        flash('seller_return_error', safe_exception_message($exception, 'We could not update that return request right now.'));
    }

    header('Location: /seller/return-view.php?id=' . $requestId);
    exit;
}

$request = $requestId > 0 ? $returnRequestService->findForSeller($requestId, $sellerId) : null;

if ($request === null) {
    flash('seller_returns_error', 'That return request could not be found in your store.');
    header('Location: /seller/returns.php');
    exit;
}

$package = $orderRepository->findPackageForSellerOrder((int) $request['order_id'], (int) $request['seller_id']);

$pageTitle = 'Return Request';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];
$pageSkeletonVariant = 'admin-form';

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Seller panel</span>
            <h1 class="hero-section__title" style="max-width:18ch;">Return Request</h1>
            <p class="hero-section__copy">Review the buyer’s request, approve next steps, and confirm when the return or refund is complete.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('orders/return-request-detail', [
                'viewer' => 'seller',
                'request' => $request,
                'package' => $package,
                'actionOptions' => $returnRequestService->sellerActionOptions($request),
                'featureAvailable' => $returnRequestService->isAvailable(),
                'notice' => flash('seller_return_notice'),
                'error' => flash('seller_return_error'),
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
