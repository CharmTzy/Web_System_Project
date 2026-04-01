<?php

declare(strict_types=1);

$appName = $appName ?? 'NovaMarket';
?>
<main class="legal-page">
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Our story</span>
            <h1 class="hero-section__title legal-page__title">About <?= e($appName) ?></h1>
            <p class="hero-section__copy legal-page__intro">
                A community-driven marketplace connecting independent sellers with everyday shoppers across Singapore.
            </p>
        </div>
    </section>

    <section class="catalog-section legal-page__section">
        <div class="container">
            <div class="legal-page__layout">
                <article class="legal-card legal-card--content">
                    <div class="legal-card__header">
                        <span class="hero-section__eyebrow">Who we are</span>
                    </div>

                    <h2>Our Mission</h2>
                    <p><?= e($appName) ?> was founded in 2026 with a simple goal: make quality products accessible to everyone while giving independent sellers a platform to grow their businesses. We believe that great products shouldn't be hard to find, and talented sellers deserve a fair marketplace to reach their customers.</p>

                    <p>Based in Singapore, we curate a diverse catalog spanning tech accessories, home essentials, kitchen tools, wellness products, and lifestyle goods. Every seller on our platform goes through a vetting process to ensure quality and reliability for our customers.</p>

                    <h2>What We Do</h2>
                    <p>We operate as a multi-vendor e-commerce marketplace where independent sellers list and manage their own products, while we handle the platform infrastructure, customer support, and quality assurance. Our three-tier system ensures smooth operations:</p>

                    <ul>
                        <li><strong>For Customers</strong> &mdash; Browse products from multiple sellers in one place, manage shipping addresses, track orders, leave reviews, and communicate directly with sellers through our built-in chat system.</li>
                        <li><strong>For Sellers</strong> &mdash; Set up a storefront, manage product listings and inventory, fulfill orders, respond to customer reviews, and handle support inquiries &mdash; all from a dedicated seller dashboard.</li>
                        <li><strong>For Administrators</strong> &mdash; Oversee the entire marketplace, vet new seller applications, manage users, monitor orders, and maintain platform-wide settings.</li>
                    </ul>

                    <h2>Our Values</h2>

                    <h3>Quality First</h3>
                    <p>Every seller on <?= e($appName) ?> is reviewed and approved before they can list products. We maintain standards so customers can shop with confidence.</p>

                    <h3>Transparency</h3>
                    <p>Clear pricing with no hidden fees. Honest product descriptions. Real customer reviews. We believe trust is built through openness.</p>

                    <h3>Community</h3>
                    <p>We are more than a shopping platform. Our chat system connects buyers directly with sellers, our review system helps the community make informed decisions, and our help center ensures everyone gets the support they need.</p>

                    <h3>Security</h3>
                    <p>Customer data protection is a priority. We use industry-standard encryption for passwords, secure payment processing through Stripe, and follow best practices for data handling and privacy.</p>

                    <h2>Our Team</h2>
                    <p><?= e($appName) ?> is built and maintained by a team of web developers as part of the INF1005 Web Systems and Technologies module at the Singapore Institute of Technology. The platform demonstrates practical application of full-stack web development using PHP, MySQL, JavaScript, and modern deployment practices on Google Cloud.</p>

                    <h2>Get in Touch</h2>
                    <p>Have questions, feedback, or interested in selling on <?= e($appName) ?>? We would love to hear from you.</p>
                    <div class="d-flex gap-3 flex-wrap" style="margin-top:1rem;">
                        <a class="btn btn-brand" href="/contact.php">Contact Us</a>
                        <a class="btn btn-brand-outline" href="/help.php">Help Center</a>
                        <a class="btn btn-brand-outline" href="/register.php">Become a Seller</a>
                    </div>
                </article>
            </div>
        </div>
    </section>
</main>
