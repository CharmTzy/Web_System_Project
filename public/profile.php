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

$pageTitle = 'My Profile';
$appName = $config['app']['name'];
$pageScript = 'profile.js';
$cartSummary = ['total_items' => 0];

require dirname(__DIR__) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="auth-section">
        <div class="container">
            <div class="auth-wrapper">
                <?= render('profile/profile-form', ['user' => $user]) ?>
            </div>
        </div>
    </section>
</main>
<?php require dirname(__DIR__) . '/resources/views/layouts/footer.php'; ?>
