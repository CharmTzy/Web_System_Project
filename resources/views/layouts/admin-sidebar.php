<?php

declare(strict_types=1);

$currentPath = $currentPath ?? '/admin/';
$appName = $appName ?? 'NovaMarket';
$sessionName = trim((string) ($sessionName ?? 'Admin'));
$sessionRole = trim((string) ($sessionRole ?? 'admin'));
$renderAdminIcon = static function (string $icon): string {
    return match ($icon) {
        'dashboard' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4.75 5.75h6.5v6.5h-6.5zm8 0h6.5v9h-6.5zm-8 8h6.5v4.5h-6.5zm8 2.5h6.5v2h-6.5z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'profile' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'users' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm7 1.5a2.5 2.5 0 1 0 0-5M4.5 19a4.5 4.5 0 0 1 9 0M14 19a3.5 3.5 0 0 1 6 0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'orders' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 6.5h10M7 10.5h10M7 14.5h6M6.5 3.5h11A1.5 1.5 0 0 1 19 5v14.5l-3-1.8-3 1.8-3-1.8-3 1.8V5A1.5 1.5 0 0 1 6.5 3.5Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'products' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5.5 7 6.5-3.5L18.5 7m-13 0 6.5 3.5L18.5 7m-13 0v9.5l6.5 3.5 6.5-3.5V7" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'addresses' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20s5-4.6 5-9a5 5 0 1 0-10 0c0 4.4 5 9 5 9Zm0-7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'coupons' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 7h8a2 2 0 0 1 2 2v1.25a2.25 2.25 0 0 0 0 4.5V16a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2v-1.25a2.25 2.25 0 0 0 0-4.5V9a2 2 0 0 1 2-2Zm2 0v11" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'help' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9.25 9.25a2.75 2.75 0 1 1 4.65 2l-1.15 1.1a2 2 0 0 0-.6 1.45v.45M12 17.5h.01" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'chat' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5.5 6.5h13a1.5 1.5 0 0 1 1.5 1.5v7a1.5 1.5 0 0 1-1.5 1.5H10l-4.5 3v-3H5.5A1.5 1.5 0 0 1 4 15V8a1.5 1.5 0 0 1 1.5-1.5Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'logout' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 6.5H7.5A1.5 1.5 0 0 0 6 8v8a1.5 1.5 0 0 0 1.5 1.5H10M13 16.5 17.5 12 13 7.5M17.5 12H9" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        default => '',
    };
};
$adminNavItems = [
    ['label' => 'Dashboard', 'href' => '/admin/', 'icon' => 'dashboard', 'match' => static fn (string $path): bool => $path === '/admin/' || $path === '/admin/index.php'],
    ['label' => 'Manage orders', 'href' => '/admin/orders.php', 'icon' => 'orders', 'match' => static fn (string $path): bool => str_starts_with($path, '/admin/orders.php') || str_starts_with($path, '/admin/order-view.php')],
    ['label' => 'Manage users', 'href' => '/admin/users.php', 'icon' => 'users', 'match' => static fn (string $path): bool => str_starts_with($path, '/admin/users.php') || str_starts_with($path, '/admin/user-edit.php')],
    ['label' => 'Manage products', 'href' => '/admin/products.php', 'icon' => 'products', 'match' => static fn (string $path): bool => str_starts_with($path, '/admin/products.php') || str_starts_with($path, '/admin/product-edit.php')],
    ['label' => 'Manage addresses', 'href' => '/admin/addresses.php', 'icon' => 'addresses', 'match' => static fn (string $path): bool => str_starts_with($path, '/admin/addresses.php') || str_starts_with($path, '/admin/address-edit.php')],
    ['label' => 'Manage coupons', 'href' => '/admin/coupons.php', 'icon' => 'coupons', 'match' => static fn (string $path): bool => str_starts_with($path, '/admin/coupons.php') || str_starts_with($path, '/admin/coupon-edit.php')],
    ['label' => 'Manage help center', 'href' => '/admin/help-questions.php', 'icon' => 'help', 'match' => static fn (string $path): bool => str_starts_with($path, '/admin/help-questions.php') || str_starts_with($path, '/admin/help-question-edit.php')],
    ['label' => 'Monitor chats', 'href' => '/admin/chat.php', 'icon' => 'chat', 'match' => static fn (string $path): bool => str_starts_with($path, '/admin/chat.php')],
];
?>
<aside id="adminSidebar" class="admin-sidebar" aria-label="Admin navigation">
    <a class="admin-sidebar__brand" href="/admin/" aria-label="<?= e($appName) ?> admin home">
        <span class="site-nav__brand-row">
            <span class="site-nav__brand-mark" aria-hidden="true">NM</span>
            <span class="site-nav__brand site-nav__brand--market"><?= e($appName) ?></span>
        </span>
        <span class="admin-sidebar__brand-subtitle">Admin Console</span>
    </a>

    <nav class="admin-sidebar__nav">
        <?php foreach ($adminNavItems as $item): ?>
            <?php $isActive = $item['match']($currentPath); ?>
            <a href="<?= e($item['href']) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                <span class="admin-sidebar__nav-icon" aria-hidden="true"><?= $renderAdminIcon((string) ($item['icon'] ?? '')) ?></span>
                <span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="admin-sidebar__footer">
        <div class="admin-sidebar__meta">
            <a href="/logout.php">
                <span class="admin-sidebar__nav-icon" aria-hidden="true"><?= $renderAdminIcon('logout') ?></span>
                <span>Sign out</span>
            </a>
        </div>

        <a class="admin-sidebar__profile" href="/admin/profile.php"<?= str_starts_with($currentPath, '/admin/profile.php') ? ' aria-current="page"' : '' ?>>
            <span class="admin-sidebar__eyebrow">Signed in as</span>
            <span class="admin-sidebar__profile-link">
                <span class="admin-sidebar__profile-mark" aria-hidden="true"><?= $renderAdminIcon('profile') ?></span>
                <span class="admin-sidebar__profile-copy">
                    <strong class="admin-sidebar__profile-name"><?= e($sessionName !== '' ? $sessionName : 'Administrator') ?></strong>
                </span>
            </span>
        </a>
    </div>
</aside>
