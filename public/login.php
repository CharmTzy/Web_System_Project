<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/bootstrap.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: /profile.php');
    exit;
}

$pageTitle = 'Sign In';
$appName = $config['app']['name'];
$pageScript = 'auth.js';
$bodyClass = 'auth-page auth-page--login';
$authFormView = 'auth/login-form';
$authPage = [
    'title' => 'Welcome back to your shopping space',
    'copy' => 'Sign in to review saved details, manage your cart, and jump back into the latest NovaMarket finds.',
    'utility_links' => [],
    'banner_title' => 'Want to browse first?',
    'banner_copy' => 'The storefront is always open if you want to explore before signing in.',
    'cta_label' => 'Back to storefront',
    'cta_href' => '/index.html',
];

require dirname(__DIR__) . '/resources/views/layouts/auth-page.php';
