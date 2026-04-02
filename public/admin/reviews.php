<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

require_role('admin');

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    render_error_page(503, 'Service temporarily unavailable', service_unavailable_message());
}

$reviewRepository = new \App\Repositories\ReviewRepository($connection);
$productRepository = new \App\Repositories\ProductRepository($connection);
$orderRepository = new \App\Repositories\OrderRepository($connection);

$service = new \App\Services\ReviewService(
    $reviewRepository,
    $orderRepository,
    $productRepository,
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('admin_reviews_error', 'Session expired. Please refresh and try again.');
        header('Location: /admin/reviews.php');
        exit;
    }

    try {
        $action = (string) ($_POST['action'] ?? '');
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        $adminId = (int) ($_SESSION['user_id'] ?? 0);

        if ($reviewId <= 0) {
            throw new InvalidArgumentException('Invalid review.');
        }

        switch ($action) {
            case 'flag':
                $service->flagAsAdmin(
                    $reviewId,
                    $adminId,
                    (string) ($_POST['reason'] ?? '')
                );
                flash('admin_reviews_notice', 'Review flagged successfully.');
                break;

            case 'hide':
                $service->hideAsAdmin(
                    $reviewId,
                    $adminId,
                    isset($_POST['reason']) ? (string) $_POST['reason'] : null
                );
                flash('admin_reviews_notice', 'Review hidden successfully.');
                break;

              case 'restore':
                $service->restoreAsAdmin($reviewId);
                flash('admin_reviews_notice', 'Review restored successfully.');
                break;

            case 'admin-delete':
                $service->deleteAsAdmin($reviewId);
                flash('admin_reviews_notice', 'Review deleted successfully.');
                break;
        }
    } catch (\Throwable $exception) {
        report_exception($exception, 'admin.reviews');
        flash('admin_reviews_error', safe_exception_message($exception, 'We could not update the review right now.'));
    }

    header('Location: /admin/reviews.php');
    exit;
}

$allReviews = $reviewRepository->listAllForAdmin();
$search = trim((string) ($_GET['search'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));

$allowedStatuses = ['', 'visible', 'flagged', 'hidden', 'flagged_hidden'];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

$filteredReviews = array_values(array_filter(
    $allReviews,
    static function (array $review) use ($search, $statusFilter): bool {
        $matchesSearch = true;
        $matchesStatus = true;

        if ($search !== '') {
            $needle = mb_strtolower($search);

            $haystack = mb_strtolower(implode(' ', array_filter([
                (string) ($review['user_name'] ?? ''),
                (string) ($review['product_name'] ?? ''),
                (string) ($review['title'] ?? ''),
                (string) ($review['comment'] ?? ''),
                (string) ($review['seller_reply'] ?? ''),
                (string) ($review['flagged_reason'] ?? ''),
                (string) ($review['hide_reason'] ?? ''),
            ])));

            $matchesSearch = str_contains($haystack, $needle);
        }

        $isFlagged = !empty($review['is_flagged']);
        $isVisible = !empty($review['is_visible']);
        $isHidden = !$isVisible;

        switch ($statusFilter) {
            case 'visible':
                $matchesStatus = $isVisible && !$isFlagged;
                break;
            case 'flagged':
                $matchesStatus = $isFlagged && $isVisible;
                break;
            case 'hidden':
                $matchesStatus = $isHidden;
                break;
            case 'flagged_hidden':
                $matchesStatus = $isFlagged && $isHidden;
                break;
            default:
                $matchesStatus = true;
                break;
        }

        return $matchesSearch && $matchesStatus;
    }
));


usort(
    $filteredReviews,
    static fn(array $a, array $b): int =>
        strtotime((string) $b['updated_at']) <=> strtotime((string) $a['updated_at'])
);

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$pagination = paginate_items($filteredReviews, $page, 10);
$pageTitle = 'Manage Reviews';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Admin panel</span>
            <h1 class="hero-section__title" style="max-width:20ch;">Manage Customer Reviews</h1>
            <p class="hero-section__copy">Moderate marketplace reviews, flag problematic content, hide reviews, or remove them permanently.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('admin/review-list', [
                'reviews' => $pagination['items'],
                'pagination' => $pagination,
                'notice' => flash('admin_reviews_notice'),
                'error' => flash('admin_reviews_error'),
                'search' => $search,
                'statusFilter' => $statusFilter,
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>