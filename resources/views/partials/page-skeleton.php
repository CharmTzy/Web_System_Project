<?php

declare(strict_types=1);

$variant = (string) ($variant ?? 'panel');
?>
<div class="page-skeleton page-skeleton--<?= e($variant) ?>" data-page-skeleton aria-hidden="true">
    <?php if ($variant === 'help'): ?>
        <div class="page-skeleton__help-header">
            <div class="container">
                <div class="page-skeleton__help-brand-group">
                    <span class="page-skeleton__market-brand-eyebrow skeleton-shimmer"></span>
                    <span class="page-skeleton__market-brand-name skeleton-shimmer"></span>
                </div>
            </div>
        </div>

        <div class="page-skeleton__surface">
            <div class="container">
                <section class="page-skeleton__help-hero page-skeleton__section-card">
                    <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                    <span class="page-skeleton__section-title page-skeleton__section-title--center skeleton-shimmer"></span>
                    <span class="page-skeleton__section-copy page-skeleton__section-copy--center skeleton-shimmer"></span>
                    <span class="page-skeleton__help-search skeleton-shimmer"></span>
                </section>

                <section class="page-skeleton__section-card">
                    <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                    <span class="page-skeleton__section-title skeleton-shimmer"></span>
                    <span class="page-skeleton__section-copy skeleton-shimmer"></span>
                    <div class="page-skeleton__topic-grid">
                        <?php for ($i = 0; $i < 8; $i++): ?>
                            <span class="page-skeleton__topic-card skeleton-shimmer"></span>
                        <?php endfor; ?>
                    </div>
                </section>

                <section class="page-skeleton__section-card">
                    <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                    <span class="page-skeleton__section-title skeleton-shimmer"></span>
                    <div class="page-skeleton__question-list">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <span class="page-skeleton__question-row skeleton-shimmer"></span>
                        <?php endfor; ?>
                    </div>
                </section>
            </div>
        </div>
    <?php else: ?>
        <div class="page-skeleton__market-header">
            <div class="container">
                <div class="page-skeleton__market-main">
                    <div class="page-skeleton__market-brand-group">
                        <span class="page-skeleton__market-brand-eyebrow skeleton-shimmer"></span>
                        <span class="page-skeleton__market-brand-name skeleton-shimmer"></span>
                    </div>
                    <span class="page-skeleton__market-search skeleton-shimmer"></span>
                    <div class="page-skeleton__market-actions">
                        <span class="page-skeleton__market-chip skeleton-shimmer"></span>
                        <span class="page-skeleton__market-chip skeleton-shimmer"></span>
                    </div>
                </div>
                <div class="page-skeleton__market-subnav">
                    <span class="page-skeleton__market-link skeleton-shimmer"></span>
                    <span class="page-skeleton__market-link skeleton-shimmer"></span>
                    <span class="page-skeleton__market-link skeleton-shimmer"></span>
                    <span class="page-skeleton__market-link skeleton-shimmer"></span>
                </div>
            </div>
        </div>

        <div class="page-skeleton__surface">
            <div class="container">
                <?php if ($variant === 'catalog'): ?>
                    <span class="page-skeleton__catalog-hero skeleton-shimmer"></span>

                    <section class="page-skeleton__section-card">
                        <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                        <span class="page-skeleton__section-title skeleton-shimmer"></span>
                        <span class="page-skeleton__section-copy skeleton-shimmer"></span>
                        <div class="page-skeleton__category-grid">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <span class="page-skeleton__category-card skeleton-shimmer"></span>
                            <?php endfor; ?>
                        </div>
                    </section>

                    <section class="page-skeleton__section-card">
                        <span class="page-skeleton__section-title page-skeleton__section-title--medium skeleton-shimmer"></span>
                        <span class="page-skeleton__section-copy skeleton-shimmer"></span>
                        <div class="page-skeleton__product-grid">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <span class="page-skeleton__product-card skeleton-shimmer"></span>
                            <?php endfor; ?>
                        </div>
                    </section>
                <?php elseif ($variant === 'product'): ?>
                    <div class="page-skeleton__crumb-row">
                        <span class="page-skeleton__crumb skeleton-shimmer"></span>
                        <span class="page-skeleton__crumb skeleton-shimmer"></span>
                        <span class="page-skeleton__crumb page-skeleton__crumb--short skeleton-shimmer"></span>
                    </div>

                    <div class="page-skeleton__product-shell">
                        <section class="page-skeleton__section-card page-skeleton__product-panel">
                            <span class="page-skeleton__product-media skeleton-shimmer"></span>
                            <div class="page-skeleton__product-thumbs">
                                <?php for ($i = 0; $i < 4; $i++): ?>
                                    <span class="page-skeleton__thumb skeleton-shimmer"></span>
                                <?php endfor; ?>
                            </div>
                        </section>

                        <section class="page-skeleton__section-card page-skeleton__product-panel">
                            <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                            <span class="page-skeleton__section-title skeleton-shimmer"></span>
                            <span class="page-skeleton__section-copy skeleton-shimmer"></span>
                            <span class="page-skeleton__price skeleton-shimmer"></span>
                            <span class="page-skeleton__copy-line skeleton-shimmer"></span>
                            <span class="page-skeleton__copy-line page-skeleton__copy-line--short skeleton-shimmer"></span>
                            <div class="page-skeleton__action-row">
                                <span class="page-skeleton__button page-skeleton__button--secondary skeleton-shimmer"></span>
                                <span class="page-skeleton__button skeleton-shimmer"></span>
                            </div>
                        </section>
                    </div>

                    <div class="page-skeleton__detail-grid">
                        <section class="page-skeleton__section-card">
                            <span class="page-skeleton__section-title page-skeleton__section-title--medium skeleton-shimmer"></span>
                            <span class="page-skeleton__copy-line skeleton-shimmer"></span>
                            <span class="page-skeleton__copy-line skeleton-shimmer"></span>
                            <span class="page-skeleton__detail-card page-skeleton__detail-card--short skeleton-shimmer"></span>
                        </section>
                        <section class="page-skeleton__section-card">
                            <span class="page-skeleton__detail-card skeleton-shimmer"></span>
                        </section>
                    </div>
                <?php elseif ($variant === 'cart'): ?>
                    <div class="page-skeleton__cart-head">
                        <div class="page-skeleton__cart-heading">
                            <span class="page-skeleton__section-title page-skeleton__section-title--medium skeleton-shimmer"></span>
                            <span class="page-skeleton__section-copy skeleton-shimmer"></span>
                        </div>
                        <div class="page-skeleton__cart-summary-chips">
                            <span class="page-skeleton__market-chip skeleton-shimmer"></span>
                            <span class="page-skeleton__market-chip skeleton-shimmer"></span>
                        </div>
                    </div>

                    <div class="page-skeleton__cart-shell">
                        <div class="page-skeleton__cart-lines">
                            <?php for ($i = 0; $i < 3; $i++): ?>
                                <span class="page-skeleton__cart-line skeleton-shimmer"></span>
                            <?php endfor; ?>
                        </div>
                        <section class="page-skeleton__section-card page-skeleton__cart-summary-card">
                            <span class="page-skeleton__section-title page-skeleton__section-title--small skeleton-shimmer"></span>
                            <span class="page-skeleton__copy-line skeleton-shimmer"></span>
                            <span class="page-skeleton__copy-line page-skeleton__copy-line--short skeleton-shimmer"></span>
                            <span class="page-skeleton__button skeleton-shimmer"></span>
                        </section>
                    </div>
                <?php elseif ($variant === 'form'): ?>
                    <div class="page-skeleton__form-shell">
                        <section class="page-skeleton__section-card page-skeleton__form-card">
                            <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                            <span class="page-skeleton__section-title skeleton-shimmer"></span>
                            <div class="page-skeleton__field-stack">
                                <span class="page-skeleton__field skeleton-shimmer"></span>
                                <span class="page-skeleton__field skeleton-shimmer"></span>
                                <span class="page-skeleton__field skeleton-shimmer"></span>
                                <div class="page-skeleton__field-row">
                                    <span class="page-skeleton__field skeleton-shimmer"></span>
                                    <span class="page-skeleton__field skeleton-shimmer"></span>
                                </div>
                                <span class="page-skeleton__button skeleton-shimmer"></span>
                            </div>
                        </section>
                    </div>
                <?php else: ?>
                    <section class="page-skeleton__hero-compact">
                        <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                        <span class="page-skeleton__section-title skeleton-shimmer"></span>
                        <span class="page-skeleton__section-copy skeleton-shimmer"></span>
                    </section>

                    <div class="page-skeleton__panel-grid">
                        <section class="page-skeleton__section-card page-skeleton__panel-card page-skeleton__panel-card--primary">
                            <span class="page-skeleton__section-title page-skeleton__section-title--medium skeleton-shimmer"></span>
                            <span class="page-skeleton__copy-line skeleton-shimmer"></span>
                            <span class="page-skeleton__copy-line skeleton-shimmer"></span>
                            <span class="page-skeleton__copy-line page-skeleton__copy-line--short skeleton-shimmer"></span>
                        </section>
                        <section class="page-skeleton__section-card page-skeleton__panel-card">
                            <span class="page-skeleton__detail-card page-skeleton__detail-card--short skeleton-shimmer"></span>
                        </section>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
