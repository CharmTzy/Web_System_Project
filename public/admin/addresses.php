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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('admin_addresses_error', 'Session expired. Please refresh and try again.');
        header('Location: /admin/addresses.php');
        exit;
    }

    try {
        if (($_POST['action'] ?? '') === 'delete') {
            $service->delete((int) ($_POST['address_id'] ?? 0));
            flash('admin_addresses_notice', 'Address deleted successfully.');
        }
    } catch (\Throwable $exception) {
        report_exception($exception, 'admin.addresses');
        flash('admin_addresses_error', safe_exception_message($exception, 'We could not update the address right now.'));
    }

    header('Location: /admin/addresses.php');
    exit;
}

$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'user_id' => filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT) ?: 0,
];
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$pagination = paginate_items($service->listAddresses($filters), $page, 10);

$pageTitle = 'Manage Addresses';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Admin panel</span>
            <h1 class="hero-section__title" style="max-width:20ch;">Manage Customer Addresses</h1>
            <p class="hero-section__copy">Review, update, and remove saved delivery addresses for all customer accounts.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('admin/address-list', [
                'addresses' => $pagination['items'],
                'pagination' => $pagination,
                'customers' => $service->customerOptions(),
                'filters' => $filters,
                'notice' => flash('admin_addresses_notice'),
                'error' => flash('admin_addresses_error'),
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
