<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: /login.php');
    exit;
}

$seedProducts = product_manager_seed_products();
$categoryOptions = product_manager_categories();

$pageTitle = 'Admin Products';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];
$pageScript = 'product-manager.js';
$storageKey = 'product-manager-admin-v2-' . (string) ($_SESSION['user_id'] ?? 'guest');
$roleLabel = 'Admin';
$scopeLabel = 'All sellers';

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Admin panel</span>
            <h1 class="hero-section__title" style="max-width:20ch;">Manage Marketplace Products</h1>
            <p class="hero-section__copy">Add, edit, and remove products in a polished admin workspace while backend APIs are still pending.</p>
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
