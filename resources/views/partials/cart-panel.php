<?php

declare(strict_types=1);
?>
<?php if ($cart['is_empty']): ?>
    <div class="empty-state empty-state--compact">
        <h3>Your cart is empty.</h3>
        <p>Start exploring NovaMarket and add your favorite picks here.</p>
        <a class="btn btn-brand" href="/index.html">Browse products</a>
    </div>
<?php else: ?>
    <div class="cart-panel">
        <div class="cart-panel__items">
            <?php foreach ($cart['items'] as $item): ?>
                <?php $product = $item['product']; ?>
                <article class="cart-panel__item">
                    <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                    <div class="cart-panel__copy">
                        <h3><?= e($product['name']) ?></h3>
                        <p><?= e($product['seller_name']) ?></p>
                        <span><?= e((string) $item['quantity']) ?> x <?= e(money($product['price'])) ?></span>
                    </div>
                    <strong><?= e($item['line_total_formatted']) ?></strong>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="cart-panel__summary">
            <div class="summary-row">
                <span>Items</span>
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
        </div>
        <a class="btn btn-brand w-100" href="/cart.html">Review full cart</a>
    </div>
<?php endif; ?>
