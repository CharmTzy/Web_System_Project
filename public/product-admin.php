<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/bootstrap.php';

$seedProducts = product_manager_seed_products();
$categoryOptions = product_manager_categories();

$pageTitle = 'Product Manager';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];
$pageScript = 'product-manager.js';
$storageKey = 'product-manager-public-v2';
$roleLabel = 'Manager';
$scopeLabel = 'All sellers';

require dirname(__DIR__) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Public preview</span>
            <h1 class="hero-section__title" style="max-width:20ch;">Product Management Workspace</h1>
            <p class="hero-section__copy">Preview the admin or seller product management experience without logging in. Changes made here are stored only in this browser.</p>
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
<?php require dirname(__DIR__) . '/resources/views/layouts/footer.php'; ?>
