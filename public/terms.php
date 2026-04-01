<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/bootstrap.php';

$pageTitle = 'Terms';
$appName = $config['app']['name'];
$pageSkeletonVariant = 'legal';
$policy = [
    'eyebrow' => 'Policies',
    'title' => 'Terms of Use',
    'intro' => 'These terms describe the basic rules for using NovaMarket as a customer, seller, or administrator on the live storefront.',
    'updated' => 'March 28, 2026',
    'summary_title' => 'Key terms',
    'summary_items' => [
        'Users must provide accurate account information and keep account access secure.',
        'Seller access may be reviewed before a storefront becomes visible on the marketplace.',
        'Orders are confirmed after successful payment processing and internal order finalization.',
        'Checkout redirects customers to Stripe-hosted payment pages that identify Stripe as the payment operator on behalf of NovaMarket.',
        'Abusive, fraudulent, or disruptive use of the site may lead to account restrictions or removal.',
        'Listings, messages, and seller activity must not contain counterfeit, unsafe, illegal, or deceptive content.',
    ],
    'quick_links' => [
        ['label' => 'Privacy Policy', 'href' => '/privacy.php'],
        ['label' => 'Contact support', 'href' => '/contact.php'],
        ['label' => 'Help Center', 'href' => '/help.php'],
    ],
    'sections' => [
        [
            'title' => 'Using the site',
            'paragraphs' => [
                'NovaMarket provides a storefront experience for browsing products, creating accounts, placing orders, and managing seller and admin workflows.',
                'By using the site, you agree to use it lawfully and in a way that does not interfere with other users, storefront operations, or platform security.',
            ],
        ],
        [
            'title' => 'Accounts and roles',
            'paragraphs' => [
                'Customers, sellers, and administrators each have different access rights inside the platform.',
                'You are responsible for keeping your sign-in credentials secure and for any activity carried out through your account.',
            ],
        ],
        [
            'title' => 'Orders and payments',
            'paragraphs' => [
                'Product availability, totals, and shipping charges are shown during checkout based on the current state of the catalog and cart.',
                'Payment is processed through Stripe-hosted checkout. When you continue to payment, you will be redirected to a Stripe-hosted page that operates on behalf of NovaMarket.',
                'An order is only considered confirmed after payment succeeds and the platform marks the order as finalized.',
            ],
        ],
        [
            'title' => 'Seller listings and platform review',
            'paragraphs' => [
                'Seller accounts and storefront changes may be reviewed before they are published or updated on the marketplace.',
                'NovaMarket may remove, restrict, or moderate listings that create operational, legal, or trust issues for the platform.',
                'This includes listings or conduct involving counterfeit goods, unsafe products, prohibited items, impersonation, deceptive product claims, misleading pricing, or attempts to move payment outside the approved checkout flow.',
            ],
        ],
        [
            'title' => 'Prohibited activity',
            'paragraphs' => [
                'You may not use the site for fraud, unauthorized access, misleading activity, malicious traffic, or attempts to bypass platform restrictions.',
                'NovaMarket may suspend accounts, remove listings, cancel transactions, or escalate reports when platform activity appears abusive or unsafe.',
            ],
            'items' => [
                'Do not impersonate other people or businesses.',
                'Do not list counterfeit, unsafe, restricted, or unlawful goods.',
                'Do not make false claims about seller identity, pricing, availability, delivery timing, or refund rights.',
                'Do not ask customers to pay outside Stripe-hosted checkout or through unofficial channels.',
                'Do not attempt to scrape, attack, or disrupt the platform.',
                'Do not upload or submit content that is unlawful, deceptive, or abusive.',
            ],
        ],
        [
            'title' => 'Reporting policy and abuse issues',
            'paragraphs' => [
                'Public reports about suspicious listings, seller conduct, counterfeit goods, checkout concerns, or other abuse issues can be submitted through the Contact page and support channels shown on the site.',
                'Users should include the relevant product URL, seller name, order number, and supporting detail where available so NovaMarket can review the report efficiently.',
            ],
        ],
    ],
];

require dirname(__DIR__) . '/resources/views/layouts/header.php';
echo render('legal/policy-page', ['policy' => $policy]);
require dirname(__DIR__) . '/resources/views/layouts/footer.php';
