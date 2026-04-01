<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

require_role('admin');

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    render_error_page(503, 'Service temporarily unavailable', service_unavailable_message());
}

$returnRequestService = new \App\Services\OrderReturnRequestService(
    new \App\Repositories\OrderReturnRequestRepository($connection),
    new \App\Repositories\OrderRepository($connection)
);

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$requests = [];
$listingError = null;

try {
    $requests = $returnRequestService->listForAdmin();
} catch (\Throwable $exception) {
    report_exception($exception, 'admin.returns');
    $listingError = safe_exception_message($exception, 'We could not load return requests right now.');
}

$pagination = paginate_items($requests, $page, 10);

$pageTitle = 'Manage Returns';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];
$pageSkeletonVariant = 'admin-table';

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Admin panel</span>
            <h1 class="hero-section__title" style="max-width:18ch;">Manage Returns</h1>
            <p class="hero-section__copy">Track every return and refund request across the marketplace and step in when a seller needs support.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('orders/return-request-list', [
                'requests' => $pagination['items'],
                'pagination' => $pagination,
                'viewer' => 'admin',
                'featureAvailable' => $returnRequestService->isAvailable(),
                'notice' => flash('admin_returns_notice'),
                'error' => $listingError ?? flash('admin_returns_error'),
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
