<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/bootstrap.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: /profile.php');
    exit;
}

$pageTitle = 'Create Account';
$appName = $config['app']['name'];
$pageScript = 'auth.js';
$bodyClass = 'auth-page auth-page--register';
$authFormView = 'auth/register-form';
$authPage = [
    'title' => 'Create your account and start shopping with ease',
    'copy' => 'Set up your customer account to save details, revisit favourites, and move through checkout faster.',
    'utility_links' => [],
    'banner_title' => 'Already have an account?',
    'banner_copy' => 'Sign back in and continue browsing the products you have been exploring.',
    'cta_label' => 'Go to sign in',
    'cta_href' => '/login.php',
    'context_notice' => [
        'id' => 'register-seller-notice',
        'title' => 'Seller approval required',
        'copy' => 'Seller accounts require admin approval. You will be notified once your account has been reviewed.',
    ],
];

require dirname(__DIR__) . '/resources/views/layouts/auth-page.php';
