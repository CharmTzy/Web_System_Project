<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'customer') {
    header('Location: /login.php');
    exit;
}

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    render_error_page(503, 'Service temporarily unavailable', service_unavailable_message());
}

$addressService = new \App\Services\AddressService(
    new \App\Repositories\AddressRepository($connection)
);

$addresses = $addressService->listForUser((int) $_SESSION['user_id']);

$pageTitle = 'My Delivery Addresses';
$appName = $config['app']['name'];
$pageScript = 'addresses.js';
$cartSummary = ['total_items' => 0];

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Address book</span>
            <h1 class="hero-section__title" style="max-width:20ch;">My Delivery Addresses</h1>
            <p class="hero-section__copy">Manage the delivery and contact addresses linked to your NovaMarket account.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container" data-addresses-container>
            <?= render('customer/address-list', ['addresses' => $addresses]) ?>
            <?= render('customer/address-form') ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
