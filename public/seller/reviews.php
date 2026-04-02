<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

require_role('seller');

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
        flash('seller_reviews_error', 'Session expired. Please refresh and try again.');
        header('Location: /seller/reviews.php');
        exit;
    }

    try {
        $action = (string) ($_POST['action'] ?? '');
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        $sellerId = (int) ($_SESSION['user_id'] ?? 0);

        if ($reviewId <= 0) {
            throw new InvalidArgumentException('Invalid review.');
        }

        if ($action === 'reply') {
            $service->replyAsSeller(
                $reviewId,
                $sellerId,
                (string) ($_POST['seller_reply'] ?? '')
            );
            flash('seller_reviews_notice', 'Reply saved successfully.');
        }
    } catch (\Throwable $exception) {
        report_exception($exception, 'seller.reviews');
        flash('seller_reviews_error', safe_exception_message($exception, 'We could not update the reply right now.'));
    }

    header('Location: /seller/reviews.php');
    exit;
}

$sellerId = (int) $_SESSION['user_id'];
$products = $productRepository->listManagedProducts($sellerId);
$allReviews = [];

foreach ($products as $product) {
    $productReviews = $reviewRepository->listAllByProduct((int) $product['id']);

    foreach ($productReviews as $review) {
        $review['product_name'] = (string) ($product['name'] ?? 'Unknown product');
        $review['product_slug'] = (string) ($product['slug'] ?? '');
        $allReviews[] = $review;
    }
}

usort(
    $allReviews,
    static fn(array $a, array $b): int =>
        strtotime((string) $b['updated_at']) <=> strtotime((string) $a['updated_at'])
);

$search = trim((string) ($_GET['search'] ?? ''));
$ratingFilter = filter_input(INPUT_GET, 'rating', FILTER_VALIDATE_INT);

if ($ratingFilter !== false && $ratingFilter !== null && ($ratingFilter < 1 || $ratingFilter > 5)) {
    $ratingFilter = null;
}

$filteredReviews = array_values(array_filter(
    $allReviews,
    static function (array $review) use ($search, $ratingFilter): bool {
        $matchesSearch = true;
        $matchesRating = true;

        if ($search !== '') {
            $needle = mb_strtolower($search);

            $haystack = mb_strtolower(implode(' ', array_filter([
                (string) ($review['user_name'] ?? ''),
                (string) ($review['product_name'] ?? ''),
                (string) ($review['title'] ?? ''),
                (string) ($review['comment'] ?? ''),
                (string) ($review['seller_reply'] ?? ''),
            ])));

            $matchesSearch = str_contains($haystack, $needle);
        }

        if ($ratingFilter !== false && $ratingFilter !== null) {
            $matchesRating = (int) ($review['rating'] ?? 0) === $ratingFilter;
        }

        return $matchesSearch && $matchesRating;
    }
));

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$pagination = paginate_items($filteredReviews, $page, 10);

$pageTitle = 'Customer Reviews';
$appName = $config['app']['name'];
$cartSummary = ['total_items' => 0];
$pageSkeletonVariant = 'admin-table';

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Seller panel</span>
            <h1 class="hero-section__title" style="max-width:20ch;">Customer Reviews</h1>
            <p class="hero-section__copy">View customer feedback on your products and reply directly from your dashboard.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('seller/review-list', [
                'reviews' => $pagination['items'],
                'pagination' => $pagination,
                'notice' => flash('seller_reviews_notice'),
                'error' => flash('seller_reviews_error'),
                'search' => $search,
                'ratingFilter' => $ratingFilter,
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>