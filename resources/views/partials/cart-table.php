<?php

declare(strict_types=1);
?>
<?php if (!empty($cart['requires_sign_in'])): ?>
    <?= render('partials/cart-signin-prompt', ['login_url' => $cart['login_url'] ?? '/login.php?redirect=%2Fcart.html&cart_notice=full-cart']) ?>
<?php elseif ($cart['is_empty']): ?>
    <section class="empty-state">
        <h3>Your cart is ready.</h3>
        <p>Add products from NovaMarket to see your selected items, quantities, and totals here.</p>
        <a class="btn btn-brand" href="/index.html">Continue shopping</a>
    </section>
<?php else: ?>
    <div class="row g-4">
        <div class="col-xl-8">
            <div class="cart-lines">
                <?php foreach ($cart['items'] as $item): ?>
                    <?php $product = $item['product']; ?>
                    <?php $detailUrl = product_url($product); ?>
                    <article class="cart-line">
                        <a class="cart-line__image-link" href="<?= e($detailUrl) ?>">
                            <img class="cart-line__image" src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                        </a>
                        <div class="cart-line__content">
                            <div class="cart-line__header">
                                <div>
                                    <span class="pill-badge pill-badge--soft"><?= e($product['category_name']) ?></span>
                                    <h3><a class="cart-line__title-link" href="<?= e($detailUrl) ?>"><?= e($product['name']) ?></a></h3>
                                </div>
                                <strong><?= e($item['line_total_formatted']) ?></strong>
                            </div>
                            <p class="cart-line__seller">Sold by <?= e($product['seller_name']) ?></p>
                            <p class="cart-line__description"><?= e($product['short_description']) ?></p>
                            <div class="cart-line__controls">
                                <form class="cart-line__form" data-cart-form>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                                    <div class="quantity-picker quantity-picker--wide" data-quantity-picker>
                                        <button class="quantity-picker__button" type="button" data-quantity-button="decrement" aria-label="Decrease quantity">
                                            -
                                        </button>
                                        <label class="visually-hidden" for="cart-quantity-<?= e((string) $product['id']) ?>">Quantity for <?= e($product['name']) ?></label>
                                        <input
                                            id="cart-quantity-<?= e((string) $product['id']) ?>"
                                            class="quantity-picker__input"
                                            type="number"
                                            name="quantity"
                                            value="<?= e((string) $item['quantity']) ?>"
                                            min="1"
                                            max="<?= e((string) $product['stock_quantity']) ?>"
                                            inputmode="numeric"
                                            data-quantity-input
                                            data-auto-submit="true"
                                            data-cart-quantity-input
                                        >
                                        <button class="quantity-picker__button" type="button" data-quantity-button="increment" aria-label="Increase quantity">
                                            +
                                        </button>
                                    </div>
                                </form>

                                <form data-cart-form>
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <button class="btn btn-link btn-remove px-0" type="submit">Remove</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-xl-4">
            <aside class="summary-card">
                <span class="summary-card__eyebrow">Order summary</span>
                <h3>Review your totals</h3>
                <div class="summary-card__rows">
                    <div class="summary-row">
                        <span>Total items</span>
                        <strong><?= e((string) $cart['total_items']) ?></strong>
                    </div>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <strong><?= e($cart['subtotal_formatted']) ?></strong>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <strong><?= e($cart['shipping_formatted']) ?></strong>
                    </div>
                    <div class="summary-row summary-row--grand">
                        <span>Grand total</span>
                        <strong><?= e($cart['grand_total_formatted']) ?></strong>
                    </div>
                </div>
                <button class="btn btn-brand w-100" type="button" disabled>Checkout coming soon</button>
                <p class="summary-card__note">Shipping is free once the cart subtotal reaches <?= e(money($cart['free_shipping_threshold'])) ?>.</p>
            </aside>
        </div>
    </div>
<?php endif; ?>
