<?php

declare(strict_types=1);

$policy = $policy ?? [];
$summaryItems = $policy['summary_items'] ?? [];
$sections = $policy['sections'] ?? [];
$quickLinks = $policy['quick_links'] ?? [
    ['label' => 'Contact support', 'href' => '/contact.php'],
    ['label' => 'Help Center', 'href' => '/help.php'],
    ['label' => 'Terms', 'href' => '/terms.php'],
];
?>
<main class="legal-page">
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow"><?= e((string) ($policy['eyebrow'] ?? 'Policies')) ?></span>
            <h1 class="hero-section__title legal-page__title"><?= e((string) ($policy['title'] ?? 'Policy')) ?></h1>
            <p class="hero-section__copy legal-page__intro"><?= e((string) ($policy['intro'] ?? '')) ?></p>
        </div>
    </section>

    <section class="catalog-section legal-page__section">
        <div class="container">
            <div class="legal-page__layout">
                <article class="legal-card legal-card--content">
                    <div class="legal-card__header">
                        <span class="hero-section__eyebrow">Updated</span>
                        <p class="legal-card__meta"><?= e((string) ($policy['updated'] ?? 'March 28, 2026')) ?></p>
                    </div>

                    <?php foreach ($sections as $section): ?>
                        <section class="legal-section">
                            <h2><?= e((string) ($section['title'] ?? '')) ?></h2>
                            <?php foreach (($section['paragraphs'] ?? []) as $paragraph): ?>
                                <p><?= e((string) $paragraph) ?></p>
                            <?php endforeach; ?>
                            <?php if (!empty($section['items'])): ?>
                                <ul class="legal-list">
                                    <?php foreach ($section['items'] as $item): ?>
                                        <li><?= e((string) $item) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </section>
                    <?php endforeach; ?>
                </article>

                <aside class="legal-sidebar">
                    <section class="legal-card legal-card--summary">
                        <span class="hero-section__eyebrow"><?= e((string) ($policy['summary_title'] ?? 'At a glance')) ?></span>
                        <ul class="legal-summary-list">
                            <?php foreach ($summaryItems as $item): ?>
                                <li><?= e((string) $item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>

                    <section class="legal-card legal-card--summary">
                        <span class="hero-section__eyebrow">Quick links</span>
                        <div class="legal-link-list">
                            <?php foreach ($quickLinks as $link): ?>
                                <a href="<?= e((string) ($link['href'] ?? '/')) ?>"><?= e((string) ($link['label'] ?? 'Open')) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </section>
</main>
