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

$cartService = new \App\Services\CartService(
    new \App\Repositories\ProductRepository($connection),
    $config['app'],
    new \App\Repositories\CartRepository($connection),
);
$paymentService = new \App\Services\PaymentCardService(
    new \App\Repositories\PaymentCardRepository($connection)
);

$userId = (int) $_SESSION['user_id'];
$formError = null;
$formValues = [
    'label' => 'My Card',
    'cardholder_name' => (string) ($_SESSION['user_name'] ?? ''),
    'card_brand' => 'visa',
    'card_number' => '',
    'expiry_month' => '',
    'expiry_year' => '',
    'is_default' => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $formError = 'Session expired. Please refresh and try again.';
    } else {
        $action = (string) ($_POST['action'] ?? 'create');

        try {
            if ($action === 'set_default') {
                $paymentService->setDefault((int) ($_POST['card_id'] ?? 0), $userId);
                flash('payments_notice', 'Default payment method updated.');
                header('Location: /customer/payments.php');
                exit;
            }

            if ($action === 'delete') {
                $paymentService->delete((int) ($_POST['card_id'] ?? 0), $userId);
                flash('payments_notice', 'Payment method removed.');
                header('Location: /customer/payments.php');
                exit;
            }

            $formValues = array_merge($formValues, $_POST);
            $paymentService->create($userId, $_POST);
            flash('payments_notice', 'Payment method saved successfully.');
            header('Location: /customer/payments.php');
            exit;
        } catch (\Throwable $exception) {
            report_exception($exception, 'customer.payments');
            $formError = safe_exception_message($exception, 'We could not update your payment methods right now.');
        }
    }
}

$cards = $paymentService->listForUser($userId);
$pageTitle = 'My Payments';
$appName = $config['app']['name'];
$cartSummary = $cartService->summary();

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Saved payment methods</span>
            <h1 class="hero-section__title" style="max-width:18ch;">My Payments</h1>
            <p class="hero-section__copy">Save a card for faster checkout and choose which one to use by default.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('customer/payment-center', [
                'cards' => $cards,
                'formError' => $formError,
                'formValues' => $formValues,
                'notice' => flash('payments_notice'),
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
