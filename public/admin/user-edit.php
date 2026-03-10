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

$editUser = null;
$editId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($editId) {
    $editUser = $userService->getProfile($editId);

    if ($editUser === null) {
        header('Location: /admin/users.php');
        exit;
    }
}

$pageTitle = $editUser ? 'Edit User' : 'Create User';
$appName = $config['app']['name'];
$pageScript = 'admin-users.js';
$cartSummary = ['total_items' => 0];

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="auth-section">
        <div class="container">
            <div class="auth-wrapper">
                <?= render('admin/user-form', ['editUser' => $editUser]) ?>
            </div>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
