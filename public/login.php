<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/bootstrap.php';

$redirectCandidate = trim((string) ($_GET['redirect'] ?? ''));
$authRedirect = (
    $redirectCandidate !== ''
    && str_starts_with($redirectCandidate, '/')
    && !str_starts_with($redirectCandidate, '//')
) ? $redirectCandidate : '';
$cartNotice = trim((string) ($_GET['cart_notice'] ?? ''));

$defaultAuthenticatedRedirect = match ($_SESSION['user_role'] ?? '') {
    'admin' => '/admin/',
    'seller' => '/seller/',
    default => '/index.html',
};

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . ($authRedirect !== '' ? $authRedirect : $defaultAuthenticatedRedirect));
    exit;
}

$pageTitle = 'Sign In';
$appName = $config['app']['name'];
$pageScript = 'auth.js';
$bodyClass = 'auth-page auth-page--login';
$authFormView = 'auth/login-form';
$authPage = [
    'title' => 'Welcome back to your shopping space',
    'copy' => 'Sign in to review your orders, manage your cart, and return to the latest NovaMarket finds.',
    'utility_links' => [],
    'banner_title' => 'Want to browse first?',
    'banner_copy' => 'The storefront is always open if you want to explore before signing in.',
    'cta_label' => 'Back to storefront',
    'cta_href' => '/',
];

if ($cartNotice === 'full-cart') {
    $authPage['modal_notice'] = [
        'title' => 'The full cart can only be accessed after login.',
        'copy' => 'Sign in to open your full cart. Any items you added as a guest will be kept and added to your account cart after you sign in.',
        'button_label' => 'Continue to sign in',
    ];
}

require dirname(__DIR__) . '/resources/views/layouts/auth-page.php';
