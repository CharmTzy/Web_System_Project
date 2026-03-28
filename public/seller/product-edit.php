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

$sellerId = (int) $_SESSION['user_id'];
$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT) ?: null;
$editProduct = $productId ? $service->getSellerProduct((int) $productId, $sellerId) : null;

if ($productId !== null && $editProduct === null && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('seller_products_error', 'Product not found.');
    header('Location: /seller/products.php');
    exit;
}

$formError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $formError = 'Session expired. Please refresh and try again.';
    } else {
        try {
            $savedProduct = $productId
                ? $service->updateForSeller((int) $productId, $sellerId, $_POST)
                : $service->createForSeller($sellerId, $_POST);

            flash('seller_products_notice', $productId ? 'Product updated successfully.' : 'Product created successfully.');
            header('Location: /seller/product-edit.php?id=' . $savedProduct['id']);
            exit;
        } catch (\Throwable $exception) {
            report_exception($exception, 'seller.product_edit');
            $formError = safe_exception_message($exception, 'We could not save the product right now.');
        }
    }
}

$defaults = [
    'category_id' => '',
    'sku' => '',
    'name' => '',
    'slug' => '',
    'short_description' => '',
    'description' => '',
    'price' => '',
    'compare_price' => '',
    'stock_quantity' => 0,
    'image_url' => '',
    'is_active' => 1,
    'is_featured' => 0,
];

$formValues = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? array_merge($defaults, $_POST)
    : array_merge($defaults, $editProduct ?? []);

$pageTitle = $editProduct ? 'Edit Product' : 'Add Product';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="auth-section">
        <div class="container">
            <div class="auth-wrapper">
                <?= render('seller/product-form', [
                    'editProduct' => $editProduct,
                    'categories' => $service->categories(),
                    'formValues' => $formValues,
                    'formError' => $formError,
                    'notice' => flash('seller_products_notice'),
                ]) ?>
            </div>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
