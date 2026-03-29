<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

require_role('seller');

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    render_error_page(503, 'Service temporarily unavailable', service_unavailable_message());
}

$userService = new \App\Services\UserService(
    new \App\Repositories\UserRepository($connection)
);

$profile = $userService->getProfile((int) $_SESSION['user_id']);

$pageTitle = 'Store Profile';
$appName = $config['app']['name'];
$pageScript = 'seller-store.js';
$cartSummary = ['total_items' => 0];
$pageSkeletonVariant = 'admin-form';

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Seller workspace</span>
            <h1 class="hero-section__title" style="max-width:18ch;">Store Profile</h1>
            <p class="hero-section__copy">Keep your storefront identity, slug, and support contact details current for buyers.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('seller/store-form', ['profile' => $profile]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
