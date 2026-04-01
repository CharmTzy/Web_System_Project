<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

require_role('admin');

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    render_error_page(503, 'Service temporarily unavailable', service_unavailable_message());
}

$userService = new \App\Services\UserService(
    new \App\Repositories\UserRepository($connection)
);
$productRepository = new \App\Repositories\ProductRepository($connection);
$addressRepository = new \App\Repositories\AddressRepository($connection);
$couponRepository = new \App\Repositories\CouponRepository($connection);
$helpRepository = new \App\Repositories\HelpCenterRepository($connection);
$chatRepository = new \App\Repositories\ChatRepository($connection);

$allUsers = $userService->listUsers();
$products = $productRepository->listManagedProducts();
$addresses = $addressRepository->listAll();
$coupons = $couponRepository->listAll();
$helpQuestions = $helpRepository->listAllQuestions();
$conversations = $chatRepository->listConversationsForViewer((int) $_SESSION['user_id'], 'admin');

$userRoleCounts = [
    'Admin' => 0,
    'Seller' => 0,
    'Customer' => 0,
];

foreach ($allUsers as $user) {
    $label = ucfirst((string) $user['role']);
    $userRoleCounts[$label] = ($userRoleCounts[$label] ?? 0) + 1;
}

$productCategoryCounts = [];
$activeProductCount = 0;

foreach ($products as $product) {
    $categoryName = trim((string) ($product['category_name'] ?? '')) ?: 'Uncategorized';
    $productCategoryCounts[$categoryName] = ($productCategoryCounts[$categoryName] ?? 0) + 1;

    if (!empty($product['is_active'])) {
        $activeProductCount++;
    }
}

arsort($productCategoryCounts);

$couponTypeCounts = [];
$liveCouponCount = 0;

foreach ($coupons as $coupon) {
    $typeLabel = ucwords(str_replace('_', ' ', (string) $coupon['coupon_type']));
    $couponTypeCounts[$typeLabel] = ($couponTypeCounts[$typeLabel] ?? 0) + 1;

    if (
        !empty($coupon['is_active'])
        && strtotime((string) $coupon['starts_at']) <= time()
        && strtotime((string) $coupon['ends_at']) >= time()
    ) {
        $liveCouponCount++;
    }
}

$openConversationCount = 0;
$attentionConversationCount = 0;

foreach ($conversations as $conversation) {
    if (($conversation['status'] ?? '') === 'open') {
        $openConversationCount++;
    }

    if ((int) ($conversation['unread_count'] ?? 0) > 0) {
        $attentionConversationCount++;
    }
}

$orderSummary = [
    'total_orders' => 0,
    'gross_sales' => 0.0,
    'average_order_value' => 0.0,
];
$orderStatusCounts = [];
$trendLookup = [];

try {
    $orderSummaryRow = $connection->query(
        <<<SQL
        SELECT
            COUNT(*) AS total_orders,
            COALESCE(SUM(total), 0) AS gross_sales,
            COALESCE(AVG(total), 0) AS average_order_value
        FROM orders
        SQL
    )->fetch();

    if (is_array($orderSummaryRow)) {
        $orderSummary = [
            'total_orders' => (int) ($orderSummaryRow['total_orders'] ?? 0),
            'gross_sales' => (float) ($orderSummaryRow['gross_sales'] ?? 0),
            'average_order_value' => (float) ($orderSummaryRow['average_order_value'] ?? 0),
        ];
    }

    $orderStatusStatement = $connection->query(
        <<<SQL
        SELECT status, COUNT(*) AS order_count
        FROM orders
        GROUP BY status
        ORDER BY order_count DESC, status ASC
        SQL
    );

    foreach ($orderStatusStatement->fetchAll() as $row) {
        $statusLabel = ucwords(str_replace('_', ' ', (string) $row['status']));
        $orderStatusCounts[$statusLabel] = (int) $row['order_count'];
    }

    $trendStatement = $connection->query(
        <<<SQL
        SELECT
            DATE(created_at) AS order_day,
            COUNT(*) AS order_count
        FROM orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at) ASC
        SQL
    );

    foreach ($trendStatement->fetchAll() as $row) {
        $trendLookup[(string) $row['order_day']] = (int) $row['order_count'];
    }
} catch (\Throwable $exception) {
    report_exception($exception, 'admin.dashboard');
}

$weeklyLabels = [];
$weeklyOrderCounts = [];

for ($daysBack = 6; $daysBack >= 0; $daysBack--) {
    $date = new \DateTimeImmutable('-' . $daysBack . ' days');
    $key = $date->format('Y-m-d');
    $weeklyLabels[] = $date->format('M j');
    $weeklyOrderCounts[] = $trendLookup[$key] ?? 0;
}

$topCategory = array_key_first($productCategoryCounts) ?? 'Uncategorized';
$topCategoryCount = $topCategory !== null ? (int) ($productCategoryCounts[$topCategory] ?? 0) : 0;
$sellerCount = (int) ($userRoleCounts['Seller'] ?? 0);
$customerCount = (int) ($userRoleCounts['Customer'] ?? 0);

$stats = [
    'total' => count($allUsers),
    'sellers' => $sellerCount,
    'customers' => $customerCount,
    'products' => count($products),
    'active_products' => $activeProductCount,
    'addresses' => count($addresses),
    'coupons' => count($coupons),
    'live_coupons' => $liveCouponCount,
    'help_questions' => count($helpQuestions),
    'conversations' => count($conversations),
    'open_conversations' => $openConversationCount,
    'attention_conversations' => $attentionConversationCount,
    'total_orders' => $orderSummary['total_orders'],
    'gross_sales_formatted' => money($orderSummary['gross_sales']),
    'average_order_value_formatted' => money($orderSummary['average_order_value']),
    'top_category' => $topCategory,
    'top_category_count' => $topCategoryCount,
];

$chartData = [
    'user_roles' => [
        'labels' => array_keys($userRoleCounts),
        'values' => array_values($userRoleCounts),
    ],
    'product_categories' => [
        'labels' => array_slice(array_keys($productCategoryCounts), 0, 6),
        'values' => array_slice(array_values($productCategoryCounts), 0, 6),
    ],
    'order_statuses' => [
        'labels' => array_keys($orderStatusCounts),
        'values' => array_values($orderStatusCounts),
    ],
    'weekly_orders' => [
        'labels' => $weeklyLabels,
        'values' => $weeklyOrderCounts,
    ],
];

$highlights = [
    [
        'label' => 'Top catalog category',
        'value' => $topCategory,
        'copy' => $topCategoryCount > 0 ? $topCategoryCount . ' active listings currently sit in this category.' : 'Product categories will appear here once listings are added.',
    ],
    [
        'label' => 'Buyer to seller balance',
        'value' => $customerCount . ' buyers / ' . $sellerCount . ' sellers',
        'copy' => 'Use this ratio to spot whether supply and buyer demand are growing together.',
    ],
    [
        'label' => 'Support watchlist',
        'value' => $attentionConversationCount . ' conversations need review',
        'copy' => 'Unread conversations are a good early signal for support pressure or seller response delays.',
    ],
];

$quickLinks = [
    ['label' => 'Manage orders', 'href' => '/admin/orders.php'],
    ['label' => 'Manage users', 'href' => '/admin/users.php'],
    ['label' => 'Manage products', 'href' => '/admin/products.php'],
    ['label' => 'Manage addresses', 'href' => '/admin/addresses.php'],
    ['label' => 'Manage coupons', 'href' => '/admin/coupons.php'],
    ['label' => 'Manage help center', 'href' => '/admin/help-questions.php'],
    ['label' => 'Monitor chats', 'href' => '/admin/chat.php'],
];

$pageTitle = 'Admin Dashboard';
$appName = $config['app']['name'];
$pageScript = 'admin-dashboard.js';
$cartSummary = ['total_items' => 0];

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact admin-hero-dashboard">
        <div class="container">
            <span class="hero-section__eyebrow">Admin panel</span>
            <h1 class="hero-section__title">Marketplace Operations Dashboard</h1>
            <p class="hero-section__copy">Track platform health, catalog activity, customer growth, and support pressure from one admin workspace.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('admin/dashboard', [
                'stats' => $stats,
                'chartData' => $chartData,
                'highlights' => $highlights,
                'quickLinks' => $quickLinks,
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
