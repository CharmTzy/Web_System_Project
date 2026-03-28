<?php

declare(strict_types=1);

$filters = $filters ?? ['search' => '', 'category' => ''];
$categories = $categories ?? [];
$hotQuestions = $hot_questions ?? [];
$search = (string) ($filters['search'] ?? '');
$activeCategory = (string) ($filters['category'] ?? '');
$activeCategoryName = '';

foreach ($categories as $category) {
    if (($category['slug'] ?? '') === $activeCategory) {
        $activeCategoryName = (string) ($category['name'] ?? '');
        break;
    }
}

$buildHelpUrl = static function (string $category, string $search, string $anchor = 'help-hot-questions'): string {
    $params = [];

    if ($category !== '') {
        $params['category'] = $category;
    }

    if ($search !== '') {
        $params['search'] = $search;
    }

    $url = '/help.php';

    if ($params !== []) {
        $url .= '?' . http_build_query($params);
    }

    return $anchor !== '' ? $url . '#' . $anchor : $url;
};

$renderHelpIcon = static function (string $iconKey): string {
    return match ($iconKey) {
        'shop' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 9.5 6.1 5h11.8L19 9.5M5 9.5v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8M5 9.5a2.25 2.25 0 0 0 4.5 0M9.5 9.5a2.25 2.25 0 0 0 4.5 0M14 9.5a2.25 2.25 0 0 0 4.5 0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        'tag' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 4h4.5L20 9.5 9.5 20 4 14.5V10L10 4Zm4.25 4a1.25 1.25 0 1 0 0-2.5 1.25 1.25 0 0 0 0 2.5Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        'wallet' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4.5 7.5h13a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-13a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2Zm0 0V6a1.5 1.5 0 0 1 1.5-1.5H17M16.5 13h3" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        'truck' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.5 6.5h10v8h-10v-8Zm10 2.5h3l2.5 2.5v3H13.5V9ZM8 17.5a1.75 1.75 0 1 0 0-3.5 1.75 1.75 0 0 0 0 3.5Zm8 0a1.75 1.75 0 1 0 0-3.5 1.75 1.75 0 0 0 0 3.5Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        'refresh' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 8.5H3.5V5M17 15.5h3.5V19M5.5 11a6.5 6.5 0 0 1 10.9-4.7L20.5 9M18.5 13A6.5 6.5 0 0 1 7.6 17.7L3.5 15" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        'shield' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3.5 18.5 6v5.5c0 4-2.4 7-6.5 9-4.1-2-6.5-5-6.5-9V6L12 3.5Zm-2.5 8 1.8 1.8 3.7-3.8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        'document' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3.5h7l4 4v13H7a1.5 1.5 0 0 1-1.5-1.5V5A1.5 1.5 0 0 1 7 3.5Zm7 0v4h4M9 11h6M9 14.5h6M9 18h4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        'notice' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3.5h10A1.5 1.5 0 0 1 18.5 5v14A1.5 1.5 0 0 1 17 20.5H7A1.5 1.5 0 0 1 5.5 19V5A1.5 1.5 0 0 1 7 3.5Zm5 4v5m0 3.5v.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        default => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 18.5a6.5 6.5 0 1 0 0-13 6.5 6.5 0 0 0 0 13Zm0-10v4m0 3.5v.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
    };
};
?>
<section class="help-center-page">
    <div class="container">
        <section class="help-center__hero section-block">
            <div class="help-center__hero-copy">
                <span class="hero-section__eyebrow help-center__eyebrow">NovaMarket support</span>
                <h1>How can we help today?</h1>
                <p>Find answers for orders, shipping, returns, account issues, and store policies without leaving the storefront.</p>
            </div>

            <form class="help-center-search" action="/help.php" method="get" role="search">
                <?php if ($activeCategory !== ''): ?>
                    <input type="hidden" name="category" value="<?= e($activeCategory) ?>">
                <?php endif; ?>
                <label class="visually-hidden" for="help-center-search-input">Search help topics</label>
                <input
                    id="help-center-search-input"
                    type="search"
                    name="search"
                    value="<?= e($search) ?>"
                    placeholder="Search for answers, policies, or order help"
                >
                <button type="submit" aria-label="Search help center">&#8981;</button>
            </form>
        </section>

        <section class="help-center__section section-block">
            <div class="section-heading section-heading--market help-center__section-head">
                <div>
                    <span class="hero-section__eyebrow help-center__eyebrow">Support categories</span>
                    <h2>Browse by topic</h2>
                    <p class="section-heading__copy">Start with the category that matches your question best. Click a selected category again to remove that filter.</p>
                </div>
            </div>

            <?php if ($categories === []): ?>
                <div class="help-center__empty">
                    <h3>Help categories are not available yet.</h3>
                    <p>Connect the help center tables to start showing category filters and hot questions here.</p>
                </div>
            <?php else: ?>
                <div class="help-category-grid">
                    <?php foreach ($categories as $category): ?>
                        <?php
                        $iconKey = (string) ($category['icon_key'] ?? 'general');
                        $isActiveCategory = (string) ($category['slug'] ?? '') === $activeCategory;
                        $categorySlug = (string) ($category['slug'] ?? '');
                        $categoryUrl = $isActiveCategory
                            ? $buildHelpUrl('', $search)
                            : $buildHelpUrl($categorySlug, $search);
                        ?>
                        <a
                            class="help-category-card help-category-card--<?= e($iconKey) ?><?= $isActiveCategory ? ' is-active' : '' ?>"
                            href="<?= e($categoryUrl) ?>"
                            title="<?= e($isActiveCategory ? 'Click again to remove this filter' : 'Filter by ' . (string) ($category['name'] ?? 'this category')) ?>"
                        >
                            <span class="help-category-card__icon"><?= $renderHelpIcon($iconKey) ?></span>
                            <span class="help-category-card__body">
                                <strong><?= e((string) $category['name']) ?></strong>
                                <span><?= e((string) ($category['description'] ?? '')) ?></span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="help-center__section section-block">
            <div class="section-heading section-heading--market help-center__section-head">
                <div>
                    <span class="hero-section__eyebrow help-center__eyebrow">Customer favorites</span>
                    <div>
                        <h2 id="help-hot-questions">Hot Questions</h2>
                        <p class="section-heading__copy">
                            <?php if ($search !== '' || $activeCategoryName !== ''): ?>
                                Showing the most relevant answers for your current filters.
                            <?php else: ?>
                                Popular questions customers ask most often.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <?php if ($hotQuestions === []): ?>
                <div class="help-center__empty">
                    <h3>No questions matched that search.</h3>
                    <p>Try another keyword or clear the current category filter to see more help topics.</p>
                    <a class="btn btn-brand" href="/help.php#help-hot-questions">View all hot questions</a>
                </div>
            <?php else: ?>
                <div class="help-question-list">
                    <?php foreach ($hotQuestions as $index => $question): ?>
                        <details class="help-question-card" <?= $index === 0 && ($search !== '' || $activeCategory !== '') ? 'open' : '' ?>>
                            <summary>
                                <span class="help-question-card__summary-copy">
                                    <span class="help-question-card__tag"><?= e((string) $question['category_name']) ?></span>
                                    <span><?= e((string) $question['question']) ?></span>
                                </span>
                            </summary>
                            <div class="help-question-card__answer">
                                <p><?= nl2br(e((string) $question['answer'])) ?></p>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</section>
