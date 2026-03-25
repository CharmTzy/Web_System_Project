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

$service = new \App\Services\AdminHelpCenterService(
    new \App\Repositories\HelpCenterRepository($connection)
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('admin_help_error', 'Session expired. Please refresh and try again.');
        header('Location: /admin/help-questions.php');
        exit;
    }

    try {
        if (($_POST['action'] ?? '') === 'delete') {
            $service->delete((int) ($_POST['question_id'] ?? 0));
            flash('admin_help_notice', 'Help question deleted successfully.');
        }
    } catch (\Throwable $exception) {
        flash('admin_help_error', $exception->getMessage());
    }

    header('Location: /admin/help-questions.php');
    exit;
}

$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'category_id' => filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT) ?: 0,
];

$pageTitle = 'Manage Help Center';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Admin panel</span>
            <h1 class="hero-section__title" style="max-width:20ch;">Manage Help Center</h1>
            <p class="hero-section__copy">Create, update, and remove the hot questions shown on the NovaMarket help page.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('admin/help-question-list', [
                'questions' => $service->listQuestions($filters),
                'categories' => $service->categories(),
                'filters' => $filters,
                'notice' => flash('admin_help_notice'),
                'error' => flash('admin_help_error'),
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
