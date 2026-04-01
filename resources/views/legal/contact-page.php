<?php

declare(strict_types=1);

$supportProfile = $supportProfile ?? [];
$supportEmail = trim((string) ($supportProfile['support_email'] ?? ''));
$abuseReportEmail = trim((string) ($supportProfile['abuse_report_email'] ?? ''));
$supportPhone = trim((string) ($supportProfile['support_phone'] ?? ''));
$supportLocation = trim((string) ($supportProfile['support_location'] ?? ''));
$siteHost = trim((string) ($supportProfile['site_host'] ?? 'NovaMarket'));
$siteUrl = trim((string) ($supportProfile['site_url'] ?? ''));
$appName = trim((string) ($supportProfile['app_name'] ?? 'NovaMarket'));
$paymentsMode = trim((string) ($supportProfile['payments_mode'] ?? ($supportProfile['payments_in_test_mode'] ?? false ? 'test' : 'live')));
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

                        <section class="legal-contact-card">
                            <h2>Policy and abuse reports</h2>
                            <p>Use this path to report suspicious listings, impersonation, counterfeit goods, checkout concerns, or other trust and safety issues affecting the marketplace.</p>
                            <?php if ($abuseReportEmail !== ''): ?>
                                <a class="btn btn-brand-outline" href="mailto:<?= e($abuseReportEmail) ?>">Report an issue</a>
                            <?php elseif ($supportEmail !== ''): ?>
                                <a class="btn btn-brand-outline" href="mailto:<?= e($supportEmail) ?>">Contact support</a>
                            <?php else: ?>
                                <a class="btn btn-brand-outline" href="/help.php">Open Help Center</a>
                            <?php endif; ?>
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
                            <?php if ($abuseReportEmail !== '' && $abuseReportEmail !== $supportEmail): ?>
                                <a href="mailto:<?= e($abuseReportEmail) ?>"><?= e($abuseReportEmail) ?></a>
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
                            <li>Public reporting path for suspicious listings, abuse, or trust and safety concerns</li>
                        </ul>
                    </section>

                    <section class="legal-card legal-card--summary">
                        <span class="hero-section__eyebrow">Report marketplace abuse</span>
                        <ul class="legal-summary-list">
                            <li>Report listings or behavior that appears fraudulent, counterfeit, unsafe, deceptive, or abusive.</li>
                            <li>Include the product URL, seller name, order number, and any relevant screenshots when possible.</li>
                            <?php if ($abuseReportEmail !== ''): ?>
                                <li>Send policy and abuse notices to <?= e($abuseReportEmail) ?>.</li>
                            <?php elseif ($supportEmail !== ''): ?>
                                <li>Send policy and abuse notices to <?= e($supportEmail) ?> until a dedicated abuse mailbox is configured.</li>
                            <?php else: ?>
                                <li>Configure <code>ABUSE_REPORT_EMAIL</code> or <code>SUPPORT_EMAIL</code> so the public site exposes a direct reporting route.</li>
                            <?php endif; ?>
                        </ul>
                    </section>

                    <?php if ($paymentsMode === 'unconfigured'): ?>
                        <section class="legal-card legal-card--summary">
                            <span class="hero-section__eyebrow">Environment notice</span>
                            <ul class="legal-summary-list">
                                <li>Checkout is currently unavailable because Stripe payment keys have not been configured for this environment.</li>
                                <li>Customers should not expect payment collection until NovaMarket enables a configured Stripe environment.</li>
                            </ul>
                        </section>
                    <?php elseif ($paymentsInTestMode): ?>
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
