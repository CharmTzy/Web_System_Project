<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

require_role('admin');

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    render_error_page(503, 'Service temporarily unavailable', service_unavailable_message());
}

$service = new \App\Services\AdminHelpCenterService(
    new \App\Repositories\HelpCenterRepository($connection)
);

$questionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'question_id', FILTER_VALIDATE_INT) ?: null;
$editQuestion = $questionId ? $service->getQuestion((int) $questionId) : null;

if ($questionId !== null && $editQuestion === null && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('admin_help_error', 'Help question not found.');
    header('Location: /admin/help-questions.php');
    exit;
}

$formError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $formError = 'Session expired. Please refresh and try again.';
    } else {
        try {
            $savedQuestion = $questionId
                ? $service->update((int) $questionId, $_POST)
                : $service->create($_POST);

            flash('admin_help_notice', $questionId ? 'Help question updated successfully.' : 'Help question created successfully.');
            header('Location: /admin/help-question-edit.php?id=' . $savedQuestion['id']);
            exit;
        } catch (\Throwable $exception) {
            report_exception($exception, 'admin.help_question_edit');
            $formError = safe_exception_message($exception, 'We could not save the help question right now.');
        }
    }
}

$defaults = [
    'category_id' => '',
    'question' => '',
    'answer' => '',
    'sort_order' => 1,
    'is_hot' => 1,
];

$formValues = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? array_merge($defaults, $_POST)
    : array_merge($defaults, $editQuestion ?? []);

$pageTitle = $editQuestion ? 'Edit Help Question' : 'Create Help Question';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="auth-section">
        <div class="container">
            <div class="auth-wrapper">
                <?= render('admin/help-question-form', [
                    'editQuestion' => $editQuestion,
                    'categories' => $service->categories(),
                    'formValues' => $formValues,
                    'formError' => $formError,
                    'notice' => flash('admin_help_notice'),
                ]) ?>
            </div>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
