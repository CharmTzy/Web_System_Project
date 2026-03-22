<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'seller') {
    header('Location: /login.php');
    exit;
}

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    http_response_code(503);
    echo 'Database connection required.';
    exit;
}

$userService = new \App\Services\UserService(
    new \App\Repositories\UserRepository($connection)
);
$productService = new \App\Services\ProductManagementService(
    new \App\Repositories\ProductRepository($connection)
);

$profile = $userService->getProfile((int) $_SESSION['user_id']);
$products = $productService->listSellerProducts((int) $_SESSION['user_id']);
$stats = [
    'total_products' => count($products),
    'active_products' => count(array_filter($products, static fn (array $product): bool => $product['is_active'])),
    'out_of_stock_products' => count(array_filter($products, static fn (array $product): bool => (int) $product['stock_quantity'] === 0)),
];

$pageTitle = 'Seller Dashboard';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Seller panel</span>
            <h1 class="hero-section__title" style="max-width:20ch;">Seller Dashboard</h1>
            <p class="hero-section__copy">Manage your store profile and view your seller information.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('seller/dashboard', ['profile' => $profile, 'stats' => $stats]) ?>
            <div class="d-flex gap-3 flex-wrap">
                <a class="btn btn-brand" href="/seller/products.php">Manage products</a>
                <a class="btn btn-brand-outline" href="/seller/product-edit.php">Add new product</a>
                <a class="btn btn-brand-outline" href="/seller/store-profile.php">Edit store profile</a>
            </div>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
