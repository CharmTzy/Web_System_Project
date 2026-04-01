<?php

declare(strict_types=1);

$currentPath = $currentPath ?? '/seller/';
$appName = $appName ?? 'NovaMarket';
$sessionName = trim((string) ($sessionName ?? 'Seller'));
$renderSellerIcon = static function (string $icon): string {
    return match ($icon) {
        'dashboard' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4.75 5.75h6.5v6.5h-6.5zm8 0h6.5v9h-6.5zm-8 8h6.5v4.5h-6.5zm8 2.5h6.5v2h-6.5z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'orders' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 6.5h10M7 10.5h10M7 14.5h6M6.5 3.5h11A1.5 1.5 0 0 1 19 5v14.5l-3-1.8-3 1.8-3-1.8-3 1.8V5A1.5 1.5 0 0 1 6.5 3.5Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'returns' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 6.75h10a1.75 1.75 0 0 1 1.75 1.75v8A1.75 1.75 0 0 1 17 18.25H7A1.75 1.75 0 0 1 5.25 16.5v-8A1.75 1.75 0 0 1 7 6.75Zm3.25 3.25h3.5m-5 3h6M8.5 4.75 12 2.75l3.5 2" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'products' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5.5 7 6.5-3.5L18.5 7m-13 0 6.5 3.5L18.5 7m-13 0v9.5l6.5 3.5 6.5-3.5V7" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'chat' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5.5 6.5h13a1.5 1.5 0 0 1 1.5 1.5v7a1.5 1.5 0 0 1-1.5 1.5H10l-4.5 3v-3H5.5A1.5 1.5 0 0 1 4 15V8a1.5 1.5 0 0 1 1.5-1.5Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'store' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4.5 8.5 6 5h12l1.5 3.5M5 8.5h14v9a1.5 1.5 0 0 1-1.5 1.5H6.5A1.5 1.5 0 0 1 5 17.5v-9Zm4 0v10m6-10v10" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'profile' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'logout' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 6.5H7.5A1.5 1.5 0 0 0 6 8v8a1.5 1.5 0 0 0 1.5 1.5H10M13 16.5 17.5 12 13 7.5M17.5 12H9" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        default => '',
    };
};

$sellerNavItems = [
    ['label' => 'Dashboard', 'href' => '/seller/', 'icon' => 'dashboard', 'match' => static fn (string $path): bool => $path === '/seller/' || $path === '/seller/index.php'],
    ['label' => 'Orders', 'href' => '/seller/orders.php', 'icon' => 'orders', 'match' => static fn (string $path): bool => str_starts_with($path, '/seller/orders.php') || str_starts_with($path, '/seller/order-view.php')],
    ['label' => 'Returns & refunds', 'href' => '/seller/returns.php', 'icon' => 'returns', 'match' => static fn (string $path): bool => str_starts_with($path, '/seller/returns.php') || str_starts_with($path, '/seller/return-view.php')],
    ['label' => 'Products', 'href' => '/seller/products.php', 'icon' => 'products', 'match' => static fn (string $path): bool => str_starts_with($path, '/seller/products.php') || str_starts_with($path, '/seller/product-edit.php')],
    ['label' => 'Customer reviews', 'href' => '/seller/reviews.php', 'icon' => 'chat', 'match' => static fn (string $path): bool => str_starts_with($path, '/seller/reviews.php')],
    ['label' => 'Customer chats', 'href' => '/seller/chat.php', 'icon' => 'chat', 'match' => static fn (string $path): bool => str_starts_with($path, '/seller/chat.php')],
    ['label' => 'Store profile', 'href' => '/seller/store-profile.php', 'icon' => 'store', 'match' => static fn (string $path): bool => str_starts_with($path, '/seller/store-profile.php')],
];
?>
<aside id="adminSidebar" class="admin-sidebar seller-sidebar" aria-label="Seller navigation">
    <a class="admin-sidebar__brand" href="/seller/" aria-label="<?= e($appName) ?> seller home">
        <span class="site-nav__brand-row">
            <span class="site-nav__brand-mark" aria-hidden="true">NM</span>
            <span class="site-nav__brand site-nav__brand--market"><?= e($appName) ?></span>
        </span>
        <span class="admin-sidebar__brand-subtitle">Seller Workspace</span>
    </a>

    <nav class="admin-sidebar__nav">
        <?php foreach ($sellerNavItems as $item): ?>
            <?php $isActive = $item['match']($currentPath); ?>
            <a href="<?= e($item['href']) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                <span class="admin-sidebar__nav-icon" aria-hidden="true"><?= $renderSellerIcon((string) ($item['icon'] ?? '')) ?></span>
                <span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="admin-sidebar__footer">
        <div class="admin-sidebar__meta">
            <a href="/logout.php">
                <span class="admin-sidebar__nav-icon" aria-hidden="true"><?= $renderSellerIcon('logout') ?></span>
                <span>Sign out</span>
            </a>
        </div>

        <a class="admin-sidebar__profile" href="/seller/store-profile.php"<?= str_starts_with($currentPath, '/seller/store-profile.php') ? ' aria-current="page"' : '' ?>>
            <span class="admin-sidebar__eyebrow">Signed in as</span>
            <span class="admin-sidebar__profile-link">
                <span class="admin-sidebar__profile-mark" aria-hidden="true"><?= $renderSellerIcon('profile') ?></span>
                <span class="admin-sidebar__profile-copy">
                    <strong class="admin-sidebar__profile-name"><?= e($sessionName !== '' ? $sessionName : 'Seller') ?></strong>
                </span>
            </span>
        </a>
    </div>
</aside>
