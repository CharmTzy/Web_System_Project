<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

require_role('admin');

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

$requestId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('admin_return_error', 'Session expired. Please refresh and try again.');
        header('Location: /admin/return-view.php?id=' . $requestId);
        exit;
    }

    try {
        $returnRequestService->updateForAdmin($requestId, $_POST);
        flash('admin_return_notice', 'Return request updated successfully.');
    } catch (\Throwable $exception) {
        report_exception($exception, 'admin.return-view');
        flash('admin_return_error', safe_exception_message($exception, 'We could not update that return request right now.'));
    }

    header('Location: /admin/return-view.php?id=' . $requestId);
    exit;
}

$request = $requestId > 0 ? $returnRequestService->findForAdmin($requestId) : null;

if ($request === null) {
    flash('admin_returns_error', 'That return request could not be found.');
    header('Location: /admin/returns.php');
    exit;
}

$package = $orderRepository->findPackageForAdminOrder((int) $request['order_id'], (int) $request['seller_id']);

$pageTitle = 'Return Request';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];
$pageSkeletonVariant = 'admin-form';

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Admin panel</span>
            <h1 class="hero-section__title" style="max-width:18ch;">Return Request</h1>
            <p class="hero-section__copy">Monitor how the seller is handling this request and record the final return or refund outcome.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('orders/return-request-detail', [
                'viewer' => 'admin',
                'request' => $request,
                'package' => $package,
                'actionOptions' => $returnRequestService->adminActionOptions($request),
                'featureAvailable' => $returnRequestService->isAvailable(),
                'notice' => flash('admin_return_notice'),
                'error' => flash('admin_return_error'),
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
