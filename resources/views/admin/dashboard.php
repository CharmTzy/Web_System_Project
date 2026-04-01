<?php declare(strict_types=1); ?>
<?php
$chartPayload = json_encode($chartData ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<div class="admin-dashboard" data-admin-dashboard>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script type="application/json" data-admin-dashboard-payload><?= $chartPayload !== false ? $chartPayload : '{}' ?></script>

    <section class="admin-metrics-grid">
        <article class="admin-metric-card">
            <span class="admin-metric-card__label">Total users</span>
            <strong><?= e((string) $stats['total']) ?></strong>
            <p><?= e((string) $stats['customers']) ?> buyers and <?= e((string) $stats['sellers']) ?> sellers currently have access.</p>
        </article>
        <article class="admin-metric-card">
            <span class="admin-metric-card__label">Gross sales</span>
            <strong><?= e((string) $stats['gross_sales_formatted']) ?></strong>
            <p><?= e((string) $stats['total_orders']) ?> orders placed with an average basket of <?= e((string) $stats['average_order_value_formatted']) ?>.</p>
        </article>
        <article class="admin-metric-card">
            <span class="admin-metric-card__label">Catalog health</span>
            <strong><?= e((string) $stats['active_products']) ?></strong>
            <p><?= e((string) $stats['products']) ?> total listings, led by <?= e((string) $stats['top_category']) ?>.</p>
        </article>
        <article class="admin-metric-card">
            <span class="admin-metric-card__label">Support load</span>
            <strong><?= e((string) $stats['open_conversations']) ?></strong>
            <p><?= e((string) $stats['attention_conversations']) ?> conversations still need a fresh admin review.</p>
        </article>
        <article class="admin-metric-card">
            <span class="admin-metric-card__label">Promotions</span>
            <strong><?= e((string) $stats['live_coupons']) ?></strong>
            <p><?= e((string) $stats['coupons']) ?> coupons configured across the marketplace.</p>
        </article>
        <article class="admin-metric-card">
            <span class="admin-metric-card__label">Customer profiles</span>
            <strong><?= e((string) $stats['addresses']) ?></strong>
            <p><?= e((string) $stats['help_questions']) ?> help articles are currently published.</p>
        </article>
    </section>

    <section class="admin-chart-grid">
        <article class="section-block admin-chart-card admin-chart-card--donut">
            <div class="section-block__header">
                <div>
                    <span class="results-header__eyebrow">Audience split</span>
                    <h2>User role distribution</h2>
                </div>
            </div>
            <canvas class="admin-chart admin-chart--donut" data-users-chart aria-label="User role distribution chart"></canvas>
        </article>

        <article class="section-block admin-chart-card">
            <div class="section-block__header">
                <div>
                    <span class="results-header__eyebrow">Catalog analysis</span>
                    <h2>Products by category</h2>
                </div>
            </div>
            <canvas class="admin-chart" data-category-chart aria-label="Product category chart"></canvas>
        </article>

        <article class="section-block admin-chart-card admin-chart-card--donut">
            <div class="section-block__header">
                <div>
                    <span class="results-header__eyebrow">Order pipeline</span>
                    <h2>Order status mix</h2>
                </div>
            </div>
            <canvas class="admin-chart admin-chart--donut" data-order-status-chart aria-label="Order status chart"></canvas>
        </article>

        <article class="section-block admin-chart-card">
            <div class="section-block__header">
                <div>
                    <span class="results-header__eyebrow">Weekly trend</span>
                    <h2>Orders over the last 7 days</h2>
                </div>
            </div>
            <canvas class="admin-chart" data-weekly-orders-chart aria-label="Weekly order volume chart"></canvas>
        </article>
    </section>

    <section class="admin-insight-grid">
        <article class="section-block">
            <div class="section-block__header">
                <div>
                    <span class="results-header__eyebrow">Quick actions</span>
                    <h2>Marketplace controls</h2>
                </div>
            </div>
            <div class="admin-quick-links">
                <?php foreach (($quickLinks ?? []) as $link): ?>
                    <a class="admin-quick-links__item" href="<?= e((string) $link['href']) ?>"><?= e((string) $link['label']) ?></a>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="section-block">
            <div class="section-block__header">
                <div>
                    <span class="results-header__eyebrow">Operational highlights</span>
                    <h2>What to watch next</h2>
                </div>
            </div>
            <div class="admin-highlight-list">
                <?php foreach (($highlights ?? []) as $highlight): ?>
                    <article class="admin-highlight-item">
                        <span><?= e((string) $highlight['label']) ?></span>
                        <strong><?= e((string) $highlight['value']) ?></strong>
                        <p><?= e((string) $highlight['copy']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </article>
    </section>
</div>
