<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

require_role('admin');

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    render_error_page(503, 'Service temporarily unavailable', service_unavailable_message());
}

$service = new \App\Services\AdminProductService(
    new \App\Repositories\ProductRepository($connection),
    new \App\Repositories\UserRepository($connection)
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('admin_products_error', 'Session expired. Please refresh and try again.');
        header('Location: /admin/products.php');
        exit;
    }

    try {
        if (($_POST['action'] ?? '') === 'delete') {
            $service->delete((int) ($_POST['product_id'] ?? 0));
            flash('admin_products_notice', 'Product deleted successfully.');
        }
    } catch (\Throwable $exception) {
        report_exception($exception, 'admin.products');
        flash('admin_products_error', safe_exception_message($exception, 'We could not update the product right now.'));
    }

    header('Location: /admin/products.php');
    exit;
}

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$pagination = paginate_items($service->listProducts(), $page, 10);

$pageTitle = 'Manage Products';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Admin panel</span>
            <h1 class="hero-section__title" style="max-width:20ch;">Manage Seller Products</h1>
            <p class="hero-section__copy">View and manage every storefront listing across all seller accounts.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('admin/product-list', [
                'products' => $pagination['items'],
                'pagination' => $pagination,
                'notice' => flash('admin_products_notice'),
                'error' => flash('admin_products_error'),
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
