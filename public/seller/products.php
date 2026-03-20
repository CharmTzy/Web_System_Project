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

$profile = $userService->getProfile((int) $_SESSION['user_id']);
$storeName = trim((string) ($profile['seller_profile']['store_name'] ?? $profile['name'] ?? ''));
$seedProducts = product_manager_seed_products($storeName);
$categoryOptions = product_manager_categories($storeName);

$pageTitle = 'Seller Products';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];
$pageScript = 'product-manager.js';
$storageKey = 'product-manager-seller-v3-' . (string) ($_SESSION['user_id'] ?? 'guest');
$roleLabel = 'Seller';
$scopeLabel = $storeName !== '' ? $storeName : 'Your store';

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Seller panel</span>
            <h1 class="hero-section__title" style="max-width:20ch;">Manage Your Store Products</h1>
            <p class="hero-section__copy">Organize your listings, update pricing and stock, and keep your catalog tidy without waiting on backend setup.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('partials/product-manager', [
                'seedProducts' => $seedProducts,
                'categoryOptions' => $categoryOptions,
                'storageKey' => $storageKey,
                'roleLabel' => $roleLabel,
                'scopeLabel' => $scopeLabel,
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
