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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('admin_users_error', 'Session expired. Please refresh and try again.');
        header('Location: /admin/users.php');
        exit;
    }

    try {
        if (($_POST['action'] ?? '') === 'delete') {
            $userService->adminDeleteUser(
                (int) ($_POST['user_id'] ?? 0),
                (int) $_SESSION['user_id']
            );
            flash('admin_users_notice', 'User deleted successfully.');
        }
    } catch (\Throwable $exception) {
        flash('admin_users_error', $exception->getMessage());
    }

    header('Location: /admin/users.php');
    exit;
}

$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'role' => trim((string) ($_GET['role'] ?? '')),
];

$users = $userService->listUsers(array_filter($filters));

$pageTitle = 'Manage Users';
$appName = $config['app']['name'];
$pageScript = 'admin-users.js';
$cartSummary = ['total_items' => 0];

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Admin panel</span>
            <h1 class="hero-section__title" style="max-width:20ch;">Manage Users</h1>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('admin/user-list', [
                'users' => $users,
                'filters' => $filters,
                'actingUserId' => (int) $_SESSION['user_id'],
                'notice' => flash('admin_users_notice'),
                'error' => flash('admin_users_error'),
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
