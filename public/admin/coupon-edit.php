<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

require_role('admin');

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    render_error_page(503, 'Service temporarily unavailable', service_unavailable_message());
}

$service = new \App\Services\AdminCouponService(
    new \App\Repositories\CouponRepository($connection),
    new \App\Repositories\UserRepository($connection)
);

$couponId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'coupon_id', FILTER_VALIDATE_INT) ?: null;
$editCoupon = $couponId ? $service->getCoupon((int) $couponId) : null;

if ($couponId !== null && $editCoupon === null && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('admin_coupons_error', 'Coupon not found.');
    header('Location: /admin/coupons.php');
    exit;
}

$formError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $formError = 'Session expired. Please refresh and try again.';
    } else {
        try {
            $savedCoupon = $couponId
                ? $service->update((int) $couponId, $_POST)
                : $service->create($_POST);

            flash('admin_coupons_notice', $couponId ? 'Coupon updated successfully.' : 'Coupon created successfully.');
            header('Location: /admin/coupon-edit.php?id=' . $savedCoupon['id']);
            exit;
        } catch (\Throwable $exception) {
            report_exception($exception, 'admin.coupon_edit');
            $formError = safe_exception_message($exception, 'We could not save the coupon right now.');
        }
    }
}

$defaults = [
    'code' => '',
    'title' => '',
    'description' => '',
    'coupon_type' => 'limited_time',
    'discount_type' => 'percentage',
    'discount_value' => '',
    'minimum_spend' => '',
    'seller_id' => '',
    'starts_at' => '',
    'ends_at' => '',
    'is_featured' => 0,
    'is_active' => 1,
];

$existingValues = $editCoupon ? [
    'code' => $editCoupon['code'],
    'title' => $editCoupon['title'],
    'description' => $editCoupon['description'],
    'coupon_type' => $editCoupon['coupon_type'],
    'discount_type' => $editCoupon['discount_type'],
    'discount_value' => $editCoupon['discount_value'],
    'minimum_spend' => $editCoupon['minimum_spend'],
    'seller_id' => $editCoupon['seller_id'],
    'starts_at' => date('Y-m-d\TH:i', strtotime((string) $editCoupon['starts_at'])),
    'ends_at' => date('Y-m-d\TH:i', strtotime((string) $editCoupon['ends_at'])),
    'is_featured' => $editCoupon['is_featured'],
    'is_active' => $editCoupon['is_active'],
] : [];

$formValues = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? array_merge($defaults, $_POST)
    : array_merge($defaults, $existingValues);

$pageTitle = $editCoupon ? 'Edit Coupon' : 'Create Coupon';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="auth-section">
        <div class="container">
            <div class="auth-wrapper auth-wrapper--wide">
                <?= render('admin/coupon-form', [
                    'editCoupon' => $editCoupon,
                    'formValues' => $formValues,
                    'formError' => $formError,
                    'notice' => flash('admin_coupons_notice'),
                    'sellers' => $service->sellerOptions(),
                ]) ?>
            </div>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
