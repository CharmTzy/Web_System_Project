<?php

declare(strict_types=1);

header('Content-Type: application/json');

$config = require dirname(__DIR__, 2) . '/bootstrap.php';
$services = \App\Support\AppFactory::storefront($config);
$catalogView = $services['catalog']->browse($_GET);

echo json_encode([
    'ok' => true,
    'count' => count($catalogView['products']),
    'summary' => count($catalogView['products']) . ' products available',
    'active_filter_count' => $catalogView['activeFilterCount'],
    'html' => render('partials/catalog-grid', ['products' => $catalogView['products']]),
]);

