<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use App\Services\AdminProductService;
use App\Services\ProductMediaService;
use App\Support\Database;

final class ProductEditController
{
    public function __construct(private readonly array $config)
    {
    }

    public function handle(): void
    {
        require_role('admin');

        $database = new Database($this->config['database']);
        $connection = $database->connection();

        if (!$connection) {
            render_error_page(503, 'Service temporarily unavailable', service_unavailable_message());
        }

        $productRepository = new ProductRepository($connection);
        $service = new AdminProductService(
            $productRepository,
            new UserRepository($connection),
            new ProductMediaService($productRepository, $this->config['app'])
        );

        $productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
            ?: filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT)
            ?: null;
        $editProduct = $productId ? $service->getProduct((int) $productId) : null;

        if ($productId !== null && $editProduct === null && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            flash('admin_products_error', 'Product not found.');
            header('Location: /admin/products.php');
            exit;
        }

        $formError = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf_token'] ?? null)) {
                $formError = 'Session expired. Please refresh and try again.';
            } else {
                try {
                    $savedProduct = $productId
                        ? $service->update((int) $productId, $_POST, $_FILES)
                        : $service->create($_POST, $_FILES);

                    flash('admin_products_notice', $productId ? 'Product updated successfully.' : 'Product created successfully.');
                    header('Location: /admin/product-edit.php?id=' . $savedProduct['id']);
                    exit;
                } catch (\Throwable $exception) {
                    report_exception($exception, 'admin.product_edit');
                    $formError = safe_exception_message($exception, 'We could not save the product right now.');
                }
            }
        }

        $defaults = [
            'seller_id' => '',
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
            ? array_merge($defaults, $editProduct ?? [], $_POST)
            : array_merge($defaults, $editProduct ?? []);

        $pageTitle = $editProduct ? 'Edit Product' : 'Create Product';
        $appName = $this->config['app']['name'];
        $cartSummary = ['total_items' => 0];

        require dirname(__DIR__, 3) . '/resources/views/layouts/header.php';
        ?>
        <main>
            <section class="auth-section">
                <div class="container">
                    <div class="auth-wrapper">
                        <?= render('admin/product-form', [
                            'editProduct' => $editProduct,
                            'categories' => $service->categories(),
                            'sellers' => $service->sellerOptions(),
                            'formValues' => $formValues,
                            'formError' => $formError,
                            'notice' => flash('admin_products_notice'),
                        ]) ?>
                    </div>
                </div>
            </section>
        </main>
        <?php require dirname(__DIR__, 3) . '/resources/views/layouts/footer.php';
    }
}
