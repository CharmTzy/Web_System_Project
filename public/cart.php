<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/bootstrap.php';
$services = \App\Support\AppFactory::storefront($config);
$cartSummary = $services['cart']->summary();

$pageTitle = 'Shopping Cart';
$appName = $config['app']['name'];
$pageScript = 'cart.js';

require dirname(__DIR__) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <div class="row g-4 align-items-end">
                <div class="col-lg-8">
                    <span class="hero-section__eyebrow">System 2 / Cart review</span>
                    <h1 class="hero-section__title">Update quantities, remove products, and prepare the cart for checkout integration.</h1>
                    <p class="hero-section__copy">
                        This page is the handoff point between your storefront work and the order-and-payment system your teammates are building.
                    </p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-stat-card">
                        <div>
                            <span class="hero-stat-card__label">Items in cart</span>
                            <strong><?= e((string) $cartSummary['total_items']) ?></strong>
                        </div>
                        <div>
                            <span class="hero-stat-card__label">Subtotal</span>
                            <strong><?= e($cartSummary['subtotal_formatted']) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="catalog-section">
        <div class="container">
            <div data-cart-page>
                <?= render('partials/cart-table', ['cart' => $cartSummary]) ?>
            </div>
        </div>
    </section>
</main>

<div class="offcanvas offcanvas-end cart-drawer" tabindex="-1" id="cartDrawer" aria-labelledby="cartDrawerLabel">
    <div class="offcanvas-header">
        <div>
            <span class="hero-section__eyebrow">Cart preview</span>
            <h2 class="offcanvas-title" id="cartDrawerLabel">Customer cart</h2>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" data-cart-drawer>
        <?= render('partials/cart-panel', ['cart' => $cartSummary]) ?>
    </div>
</div>

<?php require dirname(__DIR__) . '/resources/views/layouts/footer.php'; ?>

