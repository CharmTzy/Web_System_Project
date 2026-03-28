<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/bootstrap.php';

redirect_if_role_disallowed(['admin']);

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
        'Abusive, fraudulent, or disruptive use of the site may lead to account restrictions or removal.',
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
                'Payment is processed through Stripe-hosted checkout. An order is only considered confirmed after payment succeeds and the platform marks the order as finalized.',
            ],
        ],
        [
            'title' => 'Seller listings and platform review',
            'paragraphs' => [
                'Seller accounts and storefront changes may be reviewed before they are published or updated on the marketplace.',
                'NovaMarket may remove, restrict, or moderate listings that create operational, legal, or trust issues for the platform.',
            ],
        ],
        [
            'title' => 'Prohibited activity',
            'paragraphs' => [
                'You may not use the site for fraud, unauthorized access, misleading activity, malicious traffic, or attempts to bypass platform restrictions.',
            ],
            'items' => [
                'Do not impersonate other people or businesses.',
                'Do not attempt to scrape, attack, or disrupt the platform.',
                'Do not upload or submit content that is unlawful, deceptive, or abusive.',
            ],
        ],
    ],
];

require dirname(__DIR__) . '/resources/views/layouts/header.php';
echo render('legal/policy-page', ['policy' => $policy]);
require dirname(__DIR__) . '/resources/views/layouts/footer.php';
