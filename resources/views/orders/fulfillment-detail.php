<?php

declare(strict_types=1);

$fulfillment = $fulfillment ?? null;
$viewer = (string) ($viewer ?? 'admin');
$statusOptions = $statusOptions ?? [];
$notice = $notice ?? null;
$error = $error ?? null;
$isAdmin = $viewer === 'admin';
$isEditable = !empty($fulfillment['is_editable']);
?>
<?php if (!is_array($fulfillment)): ?>
    <div class="empty-state">
        <h3>Delivery package not found.</h3>
        <p><?= $isAdmin ? 'The selected delivery package could not be loaded.' : 'This delivery package could not be loaded for your store.' ?></p>
    </div>
<?php else: ?>
    <div class="delivery-workspace">
        <section class="section-block delivery-workspace__main">
            <div class="section-block__header">
                <div>
                    <span class="results-header__eyebrow"><?= $isAdmin ? 'Admin delivery control' : 'Seller delivery control' ?></span>
                    <h2>Order <?= e((string) $fulfillment['order_number']) ?></h2>
                </div>
                <span class="<?= e(delivery_status_badge_class((string) $fulfillment['status'])) ?>">
                    <?= e((string) $fulfillment['status_label']) ?>
                </span>
            </div>

            <?php if (!empty($notice)): ?>
                <div class="alert alert-success" role="alert"><?= e((string) $notice) ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert"><?= e((string) $error) ?></div>
            <?php endif; ?>

            <?php if (!$isEditable): ?>
                <div class="alert alert-warning" role="alert">
                    Delivery updates are read-only until the order fulfillment migration is applied. You can still review the items and shipping details for this package now.
                </div>
            <?php endif; ?>

            <div class="delivery-meta-grid">
                <article class="delivery-meta-card">
                    <span class="results-header__eyebrow">Package</span>
                    <strong><?= e((string) $fulfillment['item_count']) ?> items / <?= e((string) $fulfillment['item_quantity']) ?> units</strong>
                    <p>Seller subtotal <?= e((string) $fulfillment['seller_subtotal_formatted']) ?></p>
                </article>

                <article class="delivery-meta-card">
                    <span class="results-header__eyebrow">Seller</span>
                    <strong><?= e((string) $fulfillment['seller_name']) ?></strong>
                    <p><?= e((string) ($fulfillment['courier_name'] ?: 'Courier not assigned yet')) ?></p>
                </article>

                <article class="delivery-meta-card">
                    <span class="results-header__eyebrow">Customer</span>
                    <strong><?= e((string) ($fulfillment['customer_name'] ?: $fulfillment['shipping_recipient'])) ?></strong>
                    <p><?= e((string) ($fulfillment['customer_email'] ?: $fulfillment['shipping_phone'] ?: 'Customer contact available in shipping address')) ?></p>
                </article>
            </div>

            <?php if ($isEditable): ?>
                <form class="delivery-update-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <div class="delivery-update-form__grid">
                        <div class="form-group">
                            <label for="delivery-status">Delivery status</label>
                            <select id="delivery-status" class="form-select" name="status" required>
                                <?php foreach ($statusOptions as $option): ?>
                                    <option value="<?= e((string) $option['value']) ?>" <?= (string) $fulfillment['status'] === (string) $option['value'] ? 'selected' : '' ?>>
                                        <?= e((string) $option['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="delivery-courier">Courier</label>
                            <input id="delivery-courier" class="form-control" type="text" name="courier_name" maxlength="120" value="<?= e((string) ($fulfillment['courier_name'] ?? '')) ?>" placeholder="e.g. Ninja Van">
                        </div>

                        <div class="form-group">
                            <label for="delivery-tracking">Tracking number</label>
                            <input id="delivery-tracking" class="form-control" type="text" name="tracking_number" maxlength="120" value="<?= e((string) ($fulfillment['tracking_number'] ?? '')) ?>" placeholder="Tracking code">
                        </div>

                        <div class="form-group">
                            <label for="delivery-eta">Estimated delivery date</label>
                            <input id="delivery-eta" class="form-control" type="date" name="estimated_delivery_date" value="<?= e((string) ($fulfillment['estimated_delivery_date'] ?? '')) ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="delivery-note">Status note</label>
                        <textarea id="delivery-note" class="form-control" name="status_note" rows="4" maxlength="500" placeholder="Add a note the customer and admin can understand."><?= e((string) ($fulfillment['status_note'] ?? '')) ?></textarea>
                    </div>

                    <div class="d-flex flex-wrap gap-3">
                        <button class="btn btn-brand" type="submit">Save delivery update</button>
                        <a class="btn btn-brand-outline" href="<?= e($isAdmin ? '/admin/orders.php' : '/seller/orders.php') ?>">Back to packages</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="d-flex flex-wrap gap-3">
                    <a class="btn btn-brand-outline" href="<?= e($isAdmin ? '/admin/orders.php' : '/seller/orders.php') ?>">Back to packages</a>
                </div>
            <?php endif; ?>
        </section>

        <aside class="delivery-workspace__aside">
            <section class="section-block">
                <div class="section-block__header">
                    <div>
                        <span class="results-header__eyebrow">Delivery destination</span>
                        <h2>Shipping address</h2>
                    </div>
                </div>
                <div class="delivery-address-card">
                    <strong><?= e((string) $fulfillment['shipping_recipient']) ?></strong>
                    <p><?= e((string) $fulfillment['shipping_line_1']) ?></p>
                    <?php if (!empty($fulfillment['shipping_line_2'])): ?>
                        <p><?= e((string) $fulfillment['shipping_line_2']) ?></p>
                    <?php endif; ?>
                    <p><?= e(trim((string) ($fulfillment['shipping_city'] . ', ' . $fulfillment['shipping_state'] . ' ' . $fulfillment['shipping_postal_code']))) ?></p>
                    <p><?= e((string) $fulfillment['shipping_country']) ?></p>
                    <?php if (!empty($fulfillment['shipping_phone'])): ?>
                        <p><?= e((string) $fulfillment['shipping_phone']) ?></p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="section-block">
                <div class="section-block__header">
                    <div>
                        <span class="results-header__eyebrow">Delivery timeline</span>
                        <h2>Tracking summary</h2>
                    </div>
                </div>
                <div class="delivery-timeline">
                    <div>
                        <span>Status</span>
                        <strong><?= e((string) $fulfillment['status_label']) ?></strong>
                    </div>
                    <div>
                        <span>Courier</span>
                        <strong><?= e((string) ($fulfillment['courier_name'] ?: 'Pending assignment')) ?></strong>
                    </div>
                    <div>
                        <span>Tracking</span>
                        <strong><?= e((string) ($fulfillment['tracking_number'] ?: 'Pending assignment')) ?></strong>
                    </div>
                    <div>
                        <span>Estimated delivery</span>
                        <strong><?= e((string) ($fulfillment['estimated_delivery_date_formatted'] ?: 'Not set yet')) ?></strong>
                    </div>
                    <?php if (!empty($fulfillment['status_note'])): ?>
                        <div class="delivery-timeline__note">
                            <span>Latest note</span>
                            <p><?= e((string) $fulfillment['status_note']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </aside>
    </div>

    <section class="section-block mt-4">
        <div class="section-block__header">
            <div>
                <span class="results-header__eyebrow">Package contents</span>
                <h2>Items in this delivery</h2>
            </div>
        </div>

        <?php if (($fulfillment['items'] ?? []) === []): ?>
            <p class="text-muted mb-0">No items were attached to this delivery package.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Unit price</th>
                            <th>Quantity</th>
                            <th>Line total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fulfillment['items'] as $item): ?>
                            <?php
                            $productLink = $isAdmin
                                ? '/admin/product-edit.php?id=' . rawurlencode((string) $item['product_id'])
                                : '/seller/product-edit.php?id=' . rawurlencode((string) $item['product_id']);
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= e((string) $item['image_url']) ?>" alt="" style="width:52px;height:52px;border-radius:16px;object-fit:cover;border:1px solid rgba(18,85,168,0.12);">
                                        <div>
                                            <strong><?= e((string) $item['product_name']) ?></strong><br>
                                            <small class="text-muted"><?= e((string) $item['seller_name']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= e((string) $item['unit_price_formatted']) ?></td>
                                <td><?= e((string) $item['quantity']) ?></td>
                                <td>
                                    <strong><?= e((string) $item['line_total_formatted']) ?></strong><br>
                                    <a href="<?= e($productLink) ?>"><?= $isAdmin ? 'Open product record' : 'Open product editor' ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
