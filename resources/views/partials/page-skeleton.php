<?php

declare(strict_types=1);

$variant = (string) ($variant ?? 'panel');
?>
<div class="page-skeleton page-skeleton--<?= e($variant) ?>" data-page-skeleton aria-hidden="true">
    <?php if ($variant === 'auth'): ?>
        <div class="page-skeleton__auth-shell">
            <div class="page-skeleton__auth-spotlight">
                <div class="page-skeleton__auth-brand">
                    <span class="page-skeleton__auth-brand-mark skeleton-shimmer"></span>
                    <span class="page-skeleton__auth-brand-wordmark skeleton-shimmer"></span>
                </div>

                <div class="page-skeleton__auth-copy">
                    <span class="page-skeleton__auth-kicker skeleton-shimmer"></span>
                    <span class="page-skeleton__auth-title skeleton-shimmer"></span>
                    <span class="page-skeleton__auth-copy-line skeleton-shimmer"></span>
                    <span class="page-skeleton__auth-copy-line page-skeleton__auth-copy-line--short skeleton-shimmer"></span>

                    <div class="page-skeleton__auth-highlight-grid">
                        <span class="page-skeleton__auth-highlight skeleton-shimmer"></span>
                        <span class="page-skeleton__auth-highlight skeleton-shimmer"></span>
                    </div>
                </div>

                <div class="page-skeleton__auth-footer">
                    <span class="page-skeleton__auth-footer-card skeleton-shimmer"></span>
                    <span class="page-skeleton__auth-footer-note skeleton-shimmer"></span>
                </div>
            </div>

            <div class="page-skeleton__auth-panel">
                <div class="page-skeleton__auth-card">
                    <span class="page-skeleton__auth-card-eyebrow skeleton-shimmer"></span>
                    <span class="page-skeleton__auth-card-title skeleton-shimmer"></span>

                    <div class="page-skeleton__field-stack">
                        <span class="page-skeleton__field skeleton-shimmer"></span>
                        <span class="page-skeleton__field skeleton-shimmer"></span>
                        <div class="page-skeleton__field-row">
                            <span class="page-skeleton__field skeleton-shimmer"></span>
                            <span class="page-skeleton__field skeleton-shimmer"></span>
                        </div>
                        <span class="page-skeleton__field skeleton-shimmer"></span>
                        <span class="page-skeleton__button skeleton-shimmer"></span>
                    </div>

                    <span class="page-skeleton__auth-footer-link skeleton-shimmer"></span>
                </div>
            </div>
        </div>
    <?php elseif (str_starts_with($variant, 'admin-')): ?>
        <div class="page-skeleton__admin-shell">
            <aside class="page-skeleton__admin-sidebar">
                <div class="page-skeleton__admin-brand">
                    <span class="page-skeleton__admin-brand-mark skeleton-shimmer"></span>
                    <div class="page-skeleton__admin-brand-copy">
                        <span class="page-skeleton__admin-brand-line page-skeleton__admin-brand-line--title skeleton-shimmer"></span>
                        <span class="page-skeleton__admin-brand-line skeleton-shimmer"></span>
                    </div>
                </div>

                <span class="page-skeleton__admin-profile skeleton-shimmer"></span>

                <div class="page-skeleton__admin-nav">
                    <?php for ($i = 0; $i < 7; $i++): ?>
                        <span class="page-skeleton__admin-nav-item skeleton-shimmer"></span>
                    <?php endfor; ?>
                </div>

                <div class="page-skeleton__admin-meta">
                    <span class="page-skeleton__admin-meta-item skeleton-shimmer"></span>
                    <span class="page-skeleton__admin-meta-item skeleton-shimmer"></span>
                </div>
            </aside>

            <div class="page-skeleton__admin-content">
                <?php if ($variant === 'admin-dashboard'): ?>
                    <section class="page-skeleton__admin-hero">
                        <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                        <span class="page-skeleton__section-title skeleton-shimmer"></span>
                        <span class="page-skeleton__section-copy skeleton-shimmer"></span>
                    </section>

                    <div class="page-skeleton__admin-metrics">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <span class="page-skeleton__admin-metric-card skeleton-shimmer"></span>
                        <?php endfor; ?>
                    </div>

                    <div class="page-skeleton__admin-charts">
                        <?php for ($i = 0; $i < 4; $i++): ?>
                            <span class="page-skeleton__admin-chart-card skeleton-shimmer"></span>
                        <?php endfor; ?>
                    </div>
                <?php elseif ($variant === 'admin-form'): ?>
                    <section class="page-skeleton__admin-hero">
                        <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                        <span class="page-skeleton__section-title page-skeleton__section-title--medium skeleton-shimmer"></span>
                        <span class="page-skeleton__section-copy skeleton-shimmer"></span>
                    </section>

                    <section class="page-skeleton__admin-form-card">
                        <div class="page-skeleton__field-stack">
                            <span class="page-skeleton__field skeleton-shimmer"></span>
                            <span class="page-skeleton__field skeleton-shimmer"></span>
                            <span class="page-skeleton__field skeleton-shimmer"></span>
                            <div class="page-skeleton__field-row">
                                <span class="page-skeleton__field skeleton-shimmer"></span>
                                <span class="page-skeleton__field skeleton-shimmer"></span>
                            </div>
                            <span class="page-skeleton__field skeleton-shimmer"></span>
                            <span class="page-skeleton__button skeleton-shimmer"></span>
                        </div>
                    </section>
                <?php elseif ($variant === 'admin-chat'): ?>
                    <section class="page-skeleton__admin-hero">
                        <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                        <span class="page-skeleton__section-title skeleton-shimmer"></span>
                        <span class="page-skeleton__section-copy skeleton-shimmer"></span>
                    </section>

                    <div class="page-skeleton__admin-chat-shell">
                        <span class="page-skeleton__admin-chat-list skeleton-shimmer"></span>
                        <span class="page-skeleton__admin-chat-panel skeleton-shimmer"></span>
                    </div>
                <?php else: ?>
                    <section class="page-skeleton__admin-hero">
                        <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                        <span class="page-skeleton__section-title page-skeleton__section-title--medium skeleton-shimmer"></span>
                        <span class="page-skeleton__section-copy skeleton-shimmer"></span>
                    </section>

                    <div class="page-skeleton__admin-toolbar">
                        <span class="page-skeleton__admin-toolbar-control page-skeleton__admin-toolbar-control--wide skeleton-shimmer"></span>
                        <div class="page-skeleton__admin-toolbar-actions">
                            <span class="page-skeleton__admin-toolbar-control skeleton-shimmer"></span>
                            <span class="page-skeleton__admin-toolbar-control skeleton-shimmer"></span>
                        </div>
                    </div>

                    <section class="page-skeleton__admin-table-card">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <span class="page-skeleton__admin-table-row skeleton-shimmer"></span>
                        <?php endfor; ?>
                    </section>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($variant === 'help'): ?>
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
                <?php elseif ($variant === 'chat'): ?>
                    <section class="page-skeleton__hero-compact">
                        <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                        <span class="page-skeleton__section-title page-skeleton__section-title--medium skeleton-shimmer"></span>
                        <span class="page-skeleton__section-copy skeleton-shimmer"></span>
                    </section>

                    <div class="page-skeleton__chat-shell">
                        <section class="page-skeleton__section-card page-skeleton__chat-sidebar-card">
                            <span class="page-skeleton__section-title page-skeleton__section-title--small skeleton-shimmer"></span>
                            <span class="page-skeleton__section-copy skeleton-shimmer"></span>
                            <div class="page-skeleton__chat-list">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <span class="page-skeleton__chat-list-item skeleton-shimmer"></span>
                                <?php endfor; ?>
                            </div>
                        </section>

                        <section class="page-skeleton__section-card page-skeleton__chat-thread-card">
                            <span class="page-skeleton__chat-status skeleton-shimmer"></span>
                            <div class="page-skeleton__chat-thread-body">
                                <?php for ($i = 0; $i < 4; $i++): ?>
                                    <span class="page-skeleton__chat-bubble<?= $i % 2 === 1 ? ' page-skeleton__chat-bubble--reply' : '' ?> skeleton-shimmer"></span>
                                <?php endfor; ?>
                            </div>
                            <span class="page-skeleton__chat-input skeleton-shimmer"></span>
                        </section>
                    </div>
                <?php elseif ($variant === 'coupons'): ?>
                    <section class="page-skeleton__hero-compact">
                        <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                        <span class="page-skeleton__section-title page-skeleton__section-title--medium skeleton-shimmer"></span>
                        <span class="page-skeleton__section-copy skeleton-shimmer"></span>
                    </section>

                    <div class="page-skeleton__coupon-overview-grid">
                        <?php for ($i = 0; $i < 3; $i++): ?>
                            <span class="page-skeleton__coupon-overview-card skeleton-shimmer"></span>
                        <?php endfor; ?>
                    </div>

                    <section class="page-skeleton__section-card">
                        <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                        <span class="page-skeleton__section-title page-skeleton__section-title--medium skeleton-shimmer"></span>
                        <div class="page-skeleton__coupon-featured-grid">
                            <?php for ($i = 0; $i < 3; $i++): ?>
                                <span class="page-skeleton__coupon-card page-skeleton__coupon-card--featured skeleton-shimmer"></span>
                            <?php endfor; ?>
                        </div>
                    </section>

                    <div class="page-skeleton__coupon-split-grid">
                        <section class="page-skeleton__section-card">
                            <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                            <span class="page-skeleton__section-title page-skeleton__section-title--small skeleton-shimmer"></span>
                            <div class="page-skeleton__coupon-stack">
                                <?php for ($i = 0; $i < 2; $i++): ?>
                                    <span class="page-skeleton__coupon-card skeleton-shimmer"></span>
                                <?php endfor; ?>
                            </div>
                        </section>

                        <section class="page-skeleton__section-card">
                            <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                            <span class="page-skeleton__section-title page-skeleton__section-title--small skeleton-shimmer"></span>
                            <div class="page-skeleton__coupon-grid">
                                <?php for ($i = 0; $i < 2; $i++): ?>
                                    <span class="page-skeleton__coupon-card skeleton-shimmer"></span>
                                <?php endfor; ?>
                            </div>
                        </section>
                    </div>
                <?php elseif ($variant === 'orders'): ?>
                    <section class="page-skeleton__hero-compact">
                        <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                        <span class="page-skeleton__section-title page-skeleton__section-title--medium skeleton-shimmer"></span>
                        <span class="page-skeleton__section-copy skeleton-shimmer"></span>
                    </section>

                    <div class="page-skeleton__order-list">
                        <?php for ($i = 0; $i < 3; $i++): ?>
                            <section class="page-skeleton__section-card page-skeleton__order-card">
                                <div class="page-skeleton__order-head">
                                    <div class="page-skeleton__order-head-copy">
                                        <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                                        <span class="page-skeleton__section-title page-skeleton__section-title--small skeleton-shimmer"></span>
                                    </div>
                                    <span class="page-skeleton__order-total skeleton-shimmer"></span>
                                </div>

                                <div class="page-skeleton__order-meta">
                                    <span class="page-skeleton__order-meta-card skeleton-shimmer"></span>
                                    <span class="page-skeleton__order-meta-card skeleton-shimmer"></span>
                                </div>

                                <div class="page-skeleton__order-items">
                                    <?php for ($j = 0; $j < 2; $j++): ?>
                                        <span class="page-skeleton__order-item skeleton-shimmer"></span>
                                    <?php endfor; ?>
                                </div>
                            </section>
                        <?php endfor; ?>
                    </div>
                <?php elseif ($variant === 'addresses'): ?>
                    <section class="page-skeleton__hero-compact">
                        <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                        <span class="page-skeleton__section-title page-skeleton__section-title--medium skeleton-shimmer"></span>
                        <span class="page-skeleton__section-copy skeleton-shimmer"></span>
                    </section>

                    <section class="page-skeleton__section-card">
                        <div class="page-skeleton__section-head">
                            <div class="page-skeleton__section-head-copy">
                                <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                                <span class="page-skeleton__section-title page-skeleton__section-title--small skeleton-shimmer"></span>
                            </div>
                            <span class="page-skeleton__action-pill skeleton-shimmer"></span>
                        </div>

                        <div class="page-skeleton__address-grid">
                            <?php for ($i = 0; $i < 4; $i++): ?>
                                <span class="page-skeleton__address-card skeleton-shimmer"></span>
                            <?php endfor; ?>
                        </div>
                    </section>
                <?php elseif ($variant === 'checkout'): ?>
                    <section class="page-skeleton__hero-compact">
                        <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                        <span class="page-skeleton__section-title page-skeleton__section-title--medium skeleton-shimmer"></span>
                        <span class="page-skeleton__section-copy skeleton-shimmer"></span>
                    </section>

                    <div class="page-skeleton__checkout-shell">
                        <section class="page-skeleton__section-card page-skeleton__checkout-panel">
                            <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                            <span class="page-skeleton__section-title page-skeleton__section-title--small skeleton-shimmer"></span>
                            <div class="page-skeleton__checkout-choice-grid">
                                <?php for ($i = 0; $i < 3; $i++): ?>
                                    <span class="page-skeleton__checkout-choice skeleton-shimmer"></span>
                                <?php endfor; ?>
                            </div>
                            <span class="page-skeleton__button skeleton-shimmer"></span>
                        </section>

                        <section class="page-skeleton__section-card page-skeleton__checkout-summary-card">
                            <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                            <span class="page-skeleton__section-title page-skeleton__section-title--small skeleton-shimmer"></span>
                            <div class="page-skeleton__checkout-summary-lines">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <span class="page-skeleton__copy-line<?= $i === 4 ? ' page-skeleton__copy-line--short' : '' ?> skeleton-shimmer"></span>
                                <?php endfor; ?>
                            </div>
                        </section>
                    </div>
                <?php elseif ($variant === 'seller-dashboard'): ?>
                    <section class="page-skeleton__hero-compact">
                        <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                        <span class="page-skeleton__section-title page-skeleton__section-title--medium skeleton-shimmer"></span>
                        <span class="page-skeleton__section-copy skeleton-shimmer"></span>
                    </section>

                    <div class="page-skeleton__seller-stat-grid">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <span class="page-skeleton__seller-stat-card skeleton-shimmer"></span>
                        <?php endfor; ?>
                    </div>

                    <div class="page-skeleton__seller-action-row">
                        <?php for ($i = 0; $i < 4; $i++): ?>
                            <span class="page-skeleton__action-pill skeleton-shimmer"></span>
                        <?php endfor; ?>
                    </div>
                <?php elseif ($variant === 'market-table'): ?>
                    <section class="page-skeleton__hero-compact">
                        <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                        <span class="page-skeleton__section-title page-skeleton__section-title--medium skeleton-shimmer"></span>
                        <span class="page-skeleton__section-copy skeleton-shimmer"></span>
                    </section>

                    <section class="page-skeleton__section-card">
                        <div class="page-skeleton__section-head">
                            <div class="page-skeleton__section-head-copy">
                                <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                                <span class="page-skeleton__section-title page-skeleton__section-title--small skeleton-shimmer"></span>
                            </div>
                            <span class="page-skeleton__action-pill skeleton-shimmer"></span>
                        </div>

                        <div class="page-skeleton__market-table-toolbar">
                            <span class="page-skeleton__market-table-search skeleton-shimmer"></span>
                            <span class="page-skeleton__market-table-filter skeleton-shimmer"></span>
                        </div>

                        <div class="page-skeleton__market-table">
                            <?php for ($i = 0; $i < 6; $i++): ?>
                                <span class="page-skeleton__market-table-row skeleton-shimmer"></span>
                            <?php endfor; ?>
                        </div>
                    </section>
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
                <?php elseif ($variant === 'legal'): ?>
                    <section class="page-skeleton__hero-compact">
                        <span class="page-skeleton__section-kicker skeleton-shimmer"></span>
                        <span class="page-skeleton__section-title skeleton-shimmer"></span>
                        <span class="page-skeleton__section-copy skeleton-shimmer"></span>
                    </section>

                    <div class="page-skeleton__detail-grid">
                        <section class="page-skeleton__section-card page-skeleton__panel-card page-skeleton__panel-card--primary">
                            <span class="page-skeleton__section-title page-skeleton__section-title--medium skeleton-shimmer"></span>
                            <span class="page-skeleton__copy-line skeleton-shimmer"></span>
                            <span class="page-skeleton__copy-line skeleton-shimmer"></span>
                            <span class="page-skeleton__copy-line page-skeleton__copy-line--short skeleton-shimmer"></span>
                            <span class="page-skeleton__detail-card skeleton-shimmer"></span>
                        </section>
                        <div class="page-skeleton__field-stack">
                            <section class="page-skeleton__section-card">
                                <span class="page-skeleton__section-title page-skeleton__section-title--small skeleton-shimmer"></span>
                                <span class="page-skeleton__copy-line skeleton-shimmer"></span>
                                <span class="page-skeleton__copy-line page-skeleton__copy-line--short skeleton-shimmer"></span>
                            </section>
                            <section class="page-skeleton__section-card">
                                <span class="page-skeleton__section-title page-skeleton__section-title--small skeleton-shimmer"></span>
                                <span class="page-skeleton__copy-line skeleton-shimmer"></span>
                                <span class="page-skeleton__copy-line page-skeleton__copy-line--short skeleton-shimmer"></span>
                            </section>
                        </div>
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
