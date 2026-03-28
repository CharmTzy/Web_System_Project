<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/bootstrap.php';

redirect_if_role_disallowed(['admin']);

$pageTitle = 'Privacy Policy';
$appName = $config['app']['name'];
$pageSkeletonVariant = 'legal';
$policy = [
    'eyebrow' => 'Policies',
    'title' => 'Privacy Policy',
    'intro' => 'This page explains how NovaMarket handles account, order, and support information across the storefront, seller workflows, and checkout experience.',
    'updated' => 'March 28, 2026',
    'summary_title' => 'At a glance',
    'summary_items' => [
        'Account details are used to authenticate users and personalize their dashboard experience.',
        'Order, address, and support data are used to process purchases and respond to customer questions.',
        'Payments are handled through Stripe-hosted checkout rather than a custom on-site card form.',
        'Operational security headers and role access controls are used to reduce exposure to misuse.',
    ],
    'quick_links' => [
        ['label' => 'Terms of Use', 'href' => '/terms.php'],
        ['label' => 'Contact support', 'href' => '/contact.php'],
        ['label' => 'Help Center', 'href' => '/help.php'],
    ],
    'sections' => [
        [
            'title' => 'Information we collect',
            'paragraphs' => [
                'NovaMarket collects the information needed to create accounts, manage storefront roles, review orders, and support delivery workflows.',
                'Depending on how you use the platform, this can include your name, email address, password hash, phone number, saved addresses, order history, and support conversations.',
            ],
        ],
        [
            'title' => 'How we use information',
            'paragraphs' => [
                'We use account and order information to authenticate users, manage role-based access, display the right dashboard experience, and fulfill purchases placed through the storefront.',
                'We also use support and seller onboarding information to review storefront eligibility, respond to questions, and maintain platform operations.',
            ],
        ],
        [
            'title' => 'Payments and checkout',
            'paragraphs' => [
                'NovaMarket redirects customers to Stripe-hosted checkout to complete payment securely.',
                'Payment confirmation and order finalization are handled through Stripe session and webhook events after the customer leaves the storefront.',
            ],
        ],
        [
            'title' => 'Security and access control',
            'paragraphs' => [
                'The site uses role-based access restrictions, session protection, request validation, and security headers to protect storefront, customer, seller, and admin areas.',
                'We also block common exploit probes and avoid exposing raw management tools publicly.',
            ],
        ],
        [
            'title' => 'Your choices',
            'paragraphs' => [
                'Signed-in customers can manage profile details, delivery addresses, and order-related activity from their account area.',
                'If you need help with account access or order support, use the Help Center or the appropriate signed-in support flow available on the site.',
            ],
        ],
    ],
];

require dirname(__DIR__) . '/resources/views/layouts/header.php';
echo render('legal/policy-page', ['policy' => $policy]);
require dirname(__DIR__) . '/resources/views/layouts/footer.php';
