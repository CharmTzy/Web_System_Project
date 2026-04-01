<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/bootstrap.php';

$pageTitle = 'About Us';
$appName = $config['app']['name'];
$pageSkeletonVariant = 'legal';

require dirname(__DIR__) . '/resources/views/layouts/header.php';
echo render('legal/about-page', ['appName' => $appName]);
require dirname(__DIR__) . '/resources/views/layouts/footer.php';
