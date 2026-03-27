<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

require_role('seller');

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    render_error_page(503, 'Service temporarily unavailable', service_unavailable_message());
}

$service = new \App\Services\ProductManagementService(
    new \App\Repositories\ProductRepository($connection)
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('seller_products_error', 'Session expired. Please refresh and try again.');
        header('Location: /seller/products.php');
        exit;
    }

    try {
        if (($_POST['action'] ?? '') === 'delete') {
            $service->deleteForSeller(
                (int) ($_POST['product_id'] ?? 0),
                (int) $_SESSION['user_id']
            );
            flash('seller_products_notice', 'Product deleted successfully.');
        }
    } catch (\Throwable $exception) {
        report_exception($exception, 'seller.products');
        flash('seller_products_error', safe_exception_message($exception, 'We could not update the product right now.'));
    }

    header('Location: /seller/products.php');
    exit;
}

$products = $service->listSellerProducts((int) $_SESSION['user_id']);

$pageTitle = 'Manage Products';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Seller panel</span>
            <h1 class="hero-section__title" style="max-width:20ch;">Manage Products</h1>
            <p class="hero-section__copy">Create new listings, update pricing, and remove products that are no longer available.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('seller/product-list', [
                'products' => $products,
                'notice' => flash('seller_products_notice'),
                'error' => flash('seller_products_error'),
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
