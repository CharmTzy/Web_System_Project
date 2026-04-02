<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/bootstrap.php';

require_role('customer');

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    render_error_page(503, 'Service temporarily unavailable', service_unavailable_message());
}

$userService = new \App\Services\UserService(
    new \App\Repositories\UserRepository($connection)
);

$user = $userService->getProfile((int) $_SESSION['user_id']);

if ($user === null) {
    $_SESSION = [];
    header('Location: /login.php');
    exit;
}

$addressService = new \App\Services\AddressService(
    new \App\Repositories\AddressRepository($connection)
);

$addresses = $addressService->listForUser((int) $_SESSION['user_id']);

$pageTitle = 'My Profile';
$appName = $config['app']['name'];
$pageScript = 'profile.js';
$pageScriptExtra = 'addresses.js';
$cartSummary = ['total_items' => 0];

require dirname(__DIR__) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Your account</span>
            <h1 class="hero-section__title">My Profile</h1>
            <p class="hero-section__copy">Manage your personal details and delivery addresses.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5">
                    <?= render('profile/profile-form', ['user' => $user]) ?>
                </div>
                <div class="col-lg-7" data-addresses-container>
                    <div class="profile-card">
                        <span class="hero-section__eyebrow">Address book</span>
                        <h2 class="auth-card__title">Delivery Addresses</h2>
                        <?= render('customer/address-list', ['addresses' => $addresses]) ?>
                        <?= render('customer/address-form') ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php require dirname(__DIR__) . '/resources/views/layouts/footer.php'; ?>
