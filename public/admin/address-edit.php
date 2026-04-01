<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

require_role('admin');

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    render_error_page(503, 'Service temporarily unavailable', service_unavailable_message());
}

$service = new \App\Services\AdminAddressService(
    new \App\Repositories\AddressRepository($connection),
    new \App\Repositories\UserRepository($connection)
);

$addressId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'address_id', FILTER_VALIDATE_INT) ?: null;
$editAddress = $addressId ? $service->getAddress((int) $addressId) : null;

if ($addressId !== null && $editAddress === null && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('admin_addresses_error', 'Address not found.');
    header('Location: /admin/addresses.php');
    exit;
}

$formError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $formError = 'Session expired. Please refresh and try again.';
    } else {
        try {
            $savedAddress = $addressId
                ? $service->update((int) $addressId, $_POST)
                : $service->create($_POST);

            flash('admin_addresses_notice', $addressId ? 'Address updated successfully.' : 'Address created successfully.');
            header('Location: /admin/address-edit.php?id=' . $savedAddress['id']);
            exit;
        } catch (\Throwable $exception) {
            report_exception($exception, 'admin.address_edit');
            $formError = safe_exception_message($exception, 'We could not save the address right now.');
        }
    }
}

$defaults = [
    'user_id' => '',
    'label' => 'Home',
    'recipient' => '',
    'line_1' => '',
    'line_2' => '',
    'city' => '',
    'state' => '',
    'postal_code' => '',
    'country' => 'Singapore',
    'phone' => '',
    'is_default' => 0,
];

$formValues = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? array_merge($defaults, $_POST)
    : array_merge($defaults, $editAddress ?? []);

$pageTitle = $editAddress ? 'Edit Address' : 'Create Address';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="auth-section">
        <div class="container">
            <div class="auth-wrapper">
                <?= render('admin/address-form', [
                    'editAddress' => $editAddress,
                    'customers' => $service->customerOptions(),
                    'formValues' => $formValues,
                    'formError' => $formError,
                    'notice' => flash('admin_addresses_notice'),
                ]) ?>
            </div>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
