<?php

declare(strict_types=1);

$renderCategoryIcon = static function (string $slug): string {
    return match ($slug) {
        'tech' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 5.5h10A1.5 1.5 0 0 1 18.5 7v8A1.5 1.5 0 0 1 17 16.5H7A1.5 1.5 0 0 1 5.5 15V7A1.5 1.5 0 0 1 7 5.5Zm4 11h2m-5 2h8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        'home-living' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4.5 10.5 12 4l7.5 6.5v8A1.5 1.5 0 0 1 18 20H6a1.5 1.5 0 0 1-1.5-1.5v-8ZM9 20v-5h6v5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        'kitchen' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4.5v7m-2.5-7v7m5-7v7m-2.5 0V20m7.5-15.5v6a2 2 0 0 1-2 2h-1V20" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        'wellness' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 19s-6.5-3.8-6.5-9.1A3.9 3.9 0 0 1 9.4 6c1.1 0 2.1.4 2.6 1.2.5-.8 1.5-1.2 2.6-1.2a3.9 3.9 0 0 1 3.9 3.9C18.5 15.2 12 19 12 19Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        'lifestyle' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 8.5h10l1 10H6l1-10Zm2-3h6a2 2 0 0 1 2 2v1H7v-1a2 2 0 0 1 2-2Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        default => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 18.5a6.5 6.5 0 1 0 0-13 6.5 6.5 0 0 0 0 13Zm0-10v4m0 3.5v.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
    };
};
?>
<?php foreach ($categories as $category): ?>
    <a class="category-tile" href="/?category=<?= e((string) $category['slug']) ?>">
        <span
            class="category-tile__icon category-tile__icon--<?= e((string) $category['slug']) ?>"><?= $renderCategoryIcon((string) $category['slug']) ?></span>
        <span class="category-tile__title"><?= e((string) $category['name']) ?></span>
        <span class="category-tile__meta"><?= e((string) ($category['description'] ?? '')) ?></span>
    </a>
<?php endforeach; ?>