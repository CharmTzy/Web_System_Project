<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/bootstrap.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: /profile.php');
    exit;
}

$pageTitle = 'Create Account';
$appName = $config['app']['name'];
$pageScript = 'auth.js';
$cartSummary = ['total_items' => 0];

require dirname(__DIR__) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="auth-section">
        <div class="container">
            <div class="auth-wrapper">
                <?= render('auth/register-form') ?>
            </div>
        </div>
    </section>
</main>
<?php require dirname(__DIR__) . '/resources/views/layouts/footer.php'; ?>
