<?php

declare(strict_types=1);

$supportProfile = $supportProfile ?? [];
$supportEmail = trim((string) ($supportProfile['support_email'] ?? ''));
$supportPhone = trim((string) ($supportProfile['support_phone'] ?? ''));
$supportLocation = trim((string) ($supportProfile['support_location'] ?? ''));
$siteHost = trim((string) ($supportProfile['site_host'] ?? 'NovaMarket'));
$siteUrl = trim((string) ($supportProfile['site_url'] ?? ''));
$appName = trim((string) ($supportProfile['app_name'] ?? 'NovaMarket'));
$paymentsInTestMode = !empty($supportProfile['payments_in_test_mode']);
?>
<main class="legal-page">
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Support</span>
            <h1 class="hero-section__title legal-page__title">Contact NovaMarket</h1>
            <p class="hero-section__copy legal-page__intro">
                Reach the right support path for orders, account access, seller onboarding, and policy questions.
            </p>
        </div>
    </section>

    <section class="catalog-section legal-page__section">
        <div class="container">
            <div class="legal-page__layout legal-page__layout--contact">
                <article class="legal-card legal-card--content">
                    <div class="legal-card__header">
                        <span class="hero-section__eyebrow">How we handle support</span>
                        <p class="legal-card__meta">We route support through account-linked channels whenever possible so orders and storefront activity can be verified securely.</p>
                    </div>

                    <div class="legal-contact-grid">
                        <section class="legal-contact-card">
                            <h2>Help Center</h2>
                            <p>Browse product, account, checkout, and delivery guidance before opening a request.</p>
                            <a class="btn btn-brand-outline" href="/help.php">Open Help Center</a>
                        </section>

                        <section class="legal-contact-card">
                            <h2>Customer account support</h2>
                            <p>Signed-in customers can review orders, addresses, and support conversations from their account area.</p>
                            <a class="btn btn-brand-outline" href="/login.php?redirect=%2Fprofile.php">Go to account</a>
                        </section>

                        <section class="legal-contact-card">
                            <h2>Order and checkout questions</h2>
                            <p>For order-specific issues, sign in first so support can review the correct order history and delivery details.</p>
                            <a class="btn btn-brand-outline" href="/login.php?redirect=%2Fcustomer%2Forders.php">View orders</a>
                        </section>

                        <section class="legal-contact-card">
                            <h2>Seller onboarding</h2>
                            <p>Prospective sellers can register for a storefront account. New seller accounts are reviewed before publishing.</p>
                            <a class="btn btn-brand-outline" href="/register.php">Seller registration</a>
                        </section>
                    </div>
                </article>

                <aside class="legal-sidebar">
                    <section class="legal-card legal-card--summary">
                        <span class="hero-section__eyebrow">Platform identity</span>
                        <ul class="legal-summary-list">
                            <li><?= e($appName) ?> is the storefront brand presented on this site.</li>
                            <li>Website: <?= e($siteHost) ?></li>
                            <li>Checkout is hosted by Stripe on behalf of <?= e($appName) ?> when customers continue to payment.</li>
                            <?php if ($supportLocation !== ''): ?>
                                <li>Support region: <?= e($supportLocation) ?></li>
                            <?php endif; ?>
                        </ul>
                        <div class="legal-link-list">
                            <?php if ($siteUrl !== ''): ?>
                                <a href="<?= e($siteUrl) ?>">Visit storefront</a>
                            <?php endif; ?>
                            <?php if ($supportEmail !== ''): ?>
                                <a href="mailto:<?= e($supportEmail) ?>"><?= e($supportEmail) ?></a>
                            <?php endif; ?>
                            <?php if ($supportPhone !== ''): ?>
                                <a href="tel:<?= e(preg_replace('/\s+/', '', $supportPhone) ?: $supportPhone) ?>"><?= e($supportPhone) ?></a>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="legal-card legal-card--summary">
                        <span class="hero-section__eyebrow">Response channels</span>
                        <ul class="legal-summary-list">
                            <li>Help Center for self-service support and common questions</li>
                            <li>Account-linked order and profile pages for customer support</li>
                            <li>Seller registration and approval workflow for storefront onboarding</li>
                            <li>Stripe-hosted checkout for payment processing and confirmation</li>
                        </ul>
                    </section>

                    <?php if ($paymentsInTestMode): ?>
                        <section class="legal-card legal-card--summary">
                            <span class="hero-section__eyebrow">Environment notice</span>
                            <ul class="legal-summary-list">
                                <li>Stripe test mode is currently active for this environment.</li>
                                <li>Only Stripe test cards should be used until live payment keys are enabled.</li>
                            </ul>
                        </section>
                    <?php endif; ?>

                    <section class="legal-card legal-card--summary">
                        <span class="hero-section__eyebrow">Policy links</span>
                        <div class="legal-link-list">
                            <a href="/privacy.php">Privacy Policy</a>
                            <a href="/terms.php">Terms</a>
                            <a href="/help.php">Help Center</a>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </section>
</main>
