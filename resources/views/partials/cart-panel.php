<?php

declare(strict_types=1);

$reviewUrl = (string) ($cart['review_url'] ?? '/cart.html');
$requiresLoginForFullCart = !empty($cart['requires_login_for_full_cart']);
?>
<?php if (!empty($cart['requires_sign_in'])): ?>
    <?= render('partials/cart-signin-prompt', ['compact' => true, 'login_url' => $cart['login_url'] ?? '/login.php?redirect=%2Fcart.html&cart_notice=full-cart']) ?>
<?php elseif ($cart['is_empty']): ?>
    <div class="empty-state empty-state--compact">
        <h3>Your cart is empty.</h3>
        <p>Start exploring NovaMarket and add your favorite picks here.</p>
        <a class="btn btn-brand" href="/">Browse products</a>
    </div>
<?php else: ?>
    <div class="cart-panel">
        <div class="cart-panel__items">
            <?php foreach ($cart['items'] as $item): ?>
                <?php $product = $item['product']; ?>
                <?php $detailUrl = product_url($product); ?>
                <article class="cart-panel__item">
                    <a class="cart-panel__image-link" href="<?= e($detailUrl) ?>">
                        <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                    </a>
                    <div class="cart-panel__copy">
                        <h3><a class="cart-panel__title-link" href="<?= e($detailUrl) ?>"><?= e($product['name']) ?></a></h3>
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
        <a class="btn btn-brand w-100" href="<?= e($reviewUrl) ?>">Review full cart</a>
        <?php if ($requiresLoginForFullCart): ?>
            <p class="cart-panel__note">The full cart can only be accessed after login.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>