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
    render_error_page(503, 'Service temporarily unavailable', service_unavailable_message());
}

$service = new \App\Services\AdminCouponService(
    new \App\Repositories\CouponRepository($connection),
    new \App\Repositories\UserRepository($connection)
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('admin_coupons_error', 'Session expired. Please refresh and try again.');
        header('Location: /admin/coupons.php');
        exit;
    }

    try {
        if (($_POST['action'] ?? '') === 'delete') {
            $service->delete((int) ($_POST['coupon_id'] ?? 0));
            flash('admin_coupons_notice', 'Coupon deleted successfully.');
        }
    } catch (\Throwable $exception) {
        report_exception($exception, 'admin.coupons');
        flash('admin_coupons_error', safe_exception_message($exception, 'We could not update the coupon right now.'));
    }

    header('Location: /admin/coupons.php');
    exit;
}

$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'coupon_type' => trim((string) ($_GET['coupon_type'] ?? '')),
];

$pageTitle = 'Manage Coupons';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Admin panel</span>
            <h1 class="hero-section__title" style="max-width:20ch;">Manage Coupons</h1>
            <p class="hero-section__copy">Control limited-time deals, free shipping campaigns, and seller-specific coupon offers.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('admin/coupon-list', [
                'coupons' => $service->listCoupons($filters),
                'filters' => $filters,
                'notice' => flash('admin_coupons_notice'),
                'error' => flash('admin_coupons_error'),
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
