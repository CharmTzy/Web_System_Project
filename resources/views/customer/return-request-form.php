<?php

declare(strict_types=1);

$package = $package ?? null;
$latestRequest = $latestRequest ?? null;
$requestTypeOptions = $requestTypeOptions ?? [];
$reasonOptions = $reasonOptions ?? [];
$notice = $notice ?? null;
$error = $error ?? null;
$featureAvailable = $featureAvailable ?? true;
$hasActiveRequest = !empty($hasActiveRequest);
?>
<?php if (!is_array($package)): ?>
    <div class="empty-state">
        <h3>Order package not found.</h3>
        <p>We could not find the delivered package you wanted to review.</p>
        <a class="btn btn-brand" href="/customer/orders.php">Back to orders</a>
    </div>
<?php else: ?>
    <?php if (!empty($notice)): ?>
        <div class="alert alert-success" role="alert"><?= e((string) $notice) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert"><?= e((string) $error) ?></div>
    <?php endif; ?>

    <?php if (!$featureAvailable): ?>
        <div class="alert alert-warning" role="alert">
            Return and refund requests will be available once the latest database migration is applied.
        </div>
    <?php endif; ?>

    <div class="request-workspace request-workspace--customer">
        <section class="section-block request-workspace__main">
            <div class="section-block__header">
                <div>
                    <span class="results-header__eyebrow">Customer support</span>
                    <h2>Request a return or refund</h2>
                    <p class="text-muted mb-0">This request will be shared with <?= e((string) $package['seller_name']) ?> so they can review your package.</p>
                </div>
            </div>

            <div class="request-meta-grid">
                <article class="request-meta-card">
                    <span class="request-meta-card__label">Order package</span>
                    <strong class="request-meta-card__value"><?= e((string) $package['order_number']) ?></strong>
                    <p class="request-meta-card__hint"><?= e((string) $package['item_count']) ?> items · <?= e((string) $package['seller_subtotal_formatted']) ?></p>
                </article>

                <article class="request-meta-card">
                    <span class="request-meta-card__label">Seller</span>
                    <strong class="request-meta-card__value"><?= e((string) $package['seller_name']) ?></strong>
                    <p class="request-meta-card__hint">Tracking <?= e((string) ($package['tracking_number'] ?: 'Pending assignment')) ?></p>
                </article>

                <article class="request-meta-card">
                    <span class="request-meta-card__label">Delivery status</span>
                    <strong class="request-meta-card__value"><?= e((string) $package['status_label']) ?></strong>
                    <p class="request-meta-card__hint"><?= e((string) ($package['estimated_delivery_date_formatted'] ?: 'Delivery date will update from the seller when available.')) ?></p>
                </article>
            </div>

            <?php if (is_array($latestRequest)): ?>
                <div class="request-response-card">
                    <span class="request-response-card__label">Latest request on this package</span>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="<?= e((string) $latestRequest['status_badge_class']) ?>">
                            <?= e((string) $latestRequest['status_label']) ?>
                        </span>
                        <strong><?= e((string) $latestRequest['request_type_label']) ?></strong>
                    </div>
                    <p><?= e((string) $latestRequest['reason_label']) ?></p>
                    <?php if (!empty($latestRequest['seller_response'])): ?>
                        <p><?= e((string) $latestRequest['seller_response']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ((string) $package['status'] !== 'delivered'): ?>
                <div class="alert alert-info" role="alert">
                    Return and refund requests open after the seller marks this package as delivered.
                </div>
            <?php elseif ($hasActiveRequest): ?>
                <div class="alert alert-info" role="alert">
                    You already have an active request for this package. Wait for the seller to review it before opening a new one.
                </div>
            <?php elseif ($featureAvailable): ?>
                <form class="request-decision-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="order_id" value="<?= e((string) $package['order_id']) ?>">
                    <input type="hidden" name="seller_id" value="<?= e((string) $package['seller_id']) ?>">

                    <div class="request-decision-form__grid">
                        <div class="form-group">
                            <label for="request-type">Request type</label>
                            <select id="request-type" class="form-select" name="request_type" required>
                                <option value="">Choose an option</option>
                                <?php foreach ($requestTypeOptions as $option): ?>
                                    <option value="<?= e((string) $option['value']) ?>" <?= (string) ($_POST['request_type'] ?? '') === (string) $option['value'] ? 'selected' : '' ?>>
                                        <?= e((string) $option['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="reason-code">Reason</label>
                            <select id="reason-code" class="form-select" name="reason_code" required>
                                <option value="">Choose a reason</option>
                                <?php foreach ($reasonOptions as $option): ?>
                                    <option value="<?= e((string) $option['value']) ?>" <?= (string) ($_POST['reason_code'] ?? '') === (string) $option['value'] ? 'selected' : '' ?>>
                                        <?= e((string) $option['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reason-details">Tell the seller what happened</label>
                        <textarea id="reason-details" class="form-control" name="reason_details" rows="5" maxlength="2000" placeholder="Share the issue, missing items, damage details, or what outcome you’re expecting."><?= e((string) ($_POST['reason_details'] ?? '')) ?></textarea>
                    </div>

                    <div class="d-flex flex-wrap gap-3">
                        <button class="btn btn-brand" type="submit">Submit request</button>
                        <a class="btn btn-brand-outline" href="/customer/orders.php">Back to orders</a>
                    </div>
                </form>
            <?php else: ?>
                <a class="btn btn-brand-outline" href="/customer/orders.php">Back to orders</a>
            <?php endif; ?>
        </section>

        <aside class="request-workspace__aside">
            <section class="section-block">
                <div class="section-block__header">
                    <div>
                        <span class="results-header__eyebrow">Package contents</span>
                        <h2>Items in this request</h2>
                    </div>
                </div>
                <div class="request-package-items">
                    <?php foreach (($package['items'] ?? []) as $item): ?>
                        <article class="request-package-item">
                            <img src="<?= e((string) $item['image_url']) ?>" alt="" loading="lazy">
                            <div>
                                <strong><?= e((string) $item['product_name']) ?></strong>
                                <p><?= e((string) $item['unit_price_formatted']) ?> × <?= e((string) $item['quantity']) ?></p>
                            </div>
                            <span><?= e((string) $item['line_total_formatted']) ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </aside>
    </div>
<?php endif; ?>
