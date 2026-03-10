<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
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

$allUsers = $userService->listUsers();
$stats = [
    'total' => count($allUsers),
    'sellers' => count(array_filter($allUsers, fn ($u) => $u['role'] === 'seller')),
    'customers' => count(array_filter($allUsers, fn ($u) => $u['role'] === 'customer')),
];

$pageTitle = 'Admin Dashboard';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Admin panel</span>
            <h1 class="hero-section__title" style="max-width:20ch;">User Management Dashboard</h1>
            <p class="hero-section__copy">Manage all users, create seller accounts, and control access across the platform.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('admin/dashboard', ['stats' => $stats]) ?>
            <div class="d-flex gap-3 flex-wrap">
                <a class="btn btn-brand" href="/admin/users.php">Manage users</a>
                <a class="btn btn-brand-outline" href="/admin/user-edit.php">Create new user</a>
            </div>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
