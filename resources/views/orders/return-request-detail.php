<?php

declare(strict_types=1);

$request = $request ?? null;
$package = $package ?? null;
$viewer = (string) ($viewer ?? 'seller');
$actionOptions = $actionOptions ?? [];
$notice = $notice ?? null;
$error = $error ?? null;
$featureAvailable = $featureAvailable ?? true;
$isAdmin = $viewer === 'admin';
$backHref = $isAdmin ? '/admin/returns.php' : '/seller/returns.php';
?>
<?php if (!is_array($request)): ?>
    <div class="empty-state">
        <h3>Request not found.</h3>
        <p><?= $isAdmin ? 'The selected return request could not be loaded.' : 'This return request could not be loaded for your store.' ?></p>
    </div>
<?php else: ?>
    <div class="request-workspace">
        <section class="section-block request-workspace__main">
            <div class="section-block__header">
                <div>
                    <span class="results-header__eyebrow"><?= $isAdmin ? 'Admin return control' : 'Seller return control' ?></span>
                    <h2>Order <?= e((string) $request['order_number']) ?></h2>
                </div>
                <span class="<?= e((string) $request['status_badge_class']) ?>">
                    <?= e((string) $request['status_label']) ?>
                </span>
            </div>

            <?php if (!empty($notice)): ?>
                <div class="alert alert-success" role="alert"><?= e((string) $notice) ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert"><?= e((string) $error) ?></div>
            <?php endif; ?>

            <?php if (!$featureAvailable): ?>
                <div class="alert alert-warning" role="alert">
                    Return and refund workflows become editable after the latest database migration is applied.
                </div>
            <?php endif; ?>

            <div class="request-meta-grid">
                <article class="request-meta-card">
                    <span class="request-meta-card__label">Request type</span>
                    <strong class="request-meta-card__value"><?= e((string) $request['request_type_label']) ?></strong>
                    <p class="request-meta-card__hint">Opened <?= e((string) ($request['created_at_formatted'] ?? '')) ?></p>
                </article>

                <article class="request-meta-card">
                    <span class="request-meta-card__label">Reason</span>
                    <strong class="request-meta-card__value"><?= e((string) $request['reason_label']) ?></strong>
                    <p class="request-meta-card__hint"><?= e((string) ($request['reason_details'] ?: 'No extra details were provided by the customer.')) ?></p>
                </article>

                <article class="request-meta-card">
                    <span class="request-meta-card__label"><?= $isAdmin ? 'Store' : 'Customer' ?></span>
                    <strong class="request-meta-card__value"><?= e((string) ($isAdmin ? $request['seller_name'] : $request['customer_name'])) ?></strong>
                    <p class="request-meta-card__hint"><?= e((string) ($isAdmin ? 'Customer: ' . $request['customer_name'] : $request['customer_email'])) ?></p>
                </article>
            </div>

            <?php if (!empty($request['seller_response'])): ?>
                <div class="request-response-card">
                    <span class="request-response-card__label">Latest team response</span>
                    <p><?= e((string) $request['seller_response']) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($featureAvailable && $actionOptions !== []): ?>
                <form class="request-decision-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <div class="request-decision-form__grid">
                        <div class="form-group">
                            <label for="request-status">Next action</label>
                            <select id="request-status" class="form-select" name="status" required>
                                <option value="">Choose an action</option>
                                <?php foreach ($actionOptions as $option): ?>
                                    <option value="<?= e((string) $option['value']) ?>"><?= e((string) $option['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="request-response">Response note</label>
                        <textarea id="request-response" class="form-control" name="seller_response" rows="4" maxlength="2000" placeholder="Add an update the customer can read."></textarea>
                    </div>

                    <div class="d-flex flex-wrap gap-3">
                        <button class="btn btn-brand" type="submit">Save request update</button>
                        <a class="btn btn-brand-outline" href="<?= e($backHref) ?>">Back to requests</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="d-flex flex-wrap gap-3">
                    <a class="btn btn-brand-outline" href="<?= e($backHref) ?>">Back to requests</a>
                </div>
            <?php endif; ?>
        </section>

        <aside class="request-workspace__aside">
            <section class="section-block">
                <div class="section-block__header">
                    <div>
                        <span class="results-header__eyebrow">Review timeline</span>
                        <h2>Request progress</h2>
                    </div>
                </div>
                <div class="delivery-timeline">
                    <div class="delivery-timeline__row">
                        <span>Status</span>
                        <strong><?= e((string) $request['status_label']) ?></strong>
                    </div>
                    <div class="delivery-timeline__row">
                        <span>Opened</span>
                        <strong><?= e((string) ($request['created_at_formatted'] ?? 'Not recorded')) ?></strong>
                    </div>
                    <div class="delivery-timeline__row">
                        <span>Reviewed</span>
                        <strong><?= e((string) ($request['reviewed_at_formatted'] ?? 'Pending review')) ?></strong>
                    </div>
                    <div class="delivery-timeline__row">
                        <span>Resolved</span>
                        <strong><?= e((string) ($request['resolved_at_formatted'] ?? 'Still open')) ?></strong>
                    </div>
                </div>
            </section>

            <section class="section-block">
                <div class="section-block__header">
                    <div>
                        <span class="results-header__eyebrow">Package reference</span>
                        <h2>Order package</h2>
                    </div>
                </div>

                <?php if (!is_array($package)): ?>
                    <p class="text-muted mb-0">The related package details are not available right now.</p>
                <?php else: ?>
                    <div class="delivery-address-card">
                        <span class="delivery-address-card__label">Package summary</span>
                        <strong class="delivery-address-card__name"><?= e((string) $package['seller_name']) ?></strong>
                        <div class="delivery-address-card__lines">
                            <p><?= e((string) $package['item_count']) ?> items / <?= e((string) $package['item_quantity']) ?> units</p>
                            <p>Package subtotal <?= e((string) $package['seller_subtotal_formatted']) ?></p>
                            <p>Delivery status <?= e((string) $package['status_label']) ?></p>
                            <p>Tracking <?= e((string) ($package['tracking_number'] ?: 'Pending assignment')) ?></p>
                        </div>
                    </div>

                    <?php if (($package['items'] ?? []) !== []): ?>
                        <div class="request-package-items">
                            <?php foreach ($package['items'] as $item): ?>
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
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </aside>
    </div>
<?php endif; ?>
