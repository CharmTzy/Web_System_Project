<?php

declare(strict_types=1);

header('Content-Type: application/json');

try {
    $config = require dirname(__DIR__, 2) . '/bootstrap.php';
    $services = \App\Support\AppFactory::storefront($config);
    $catalogView = $services['catalog']->browse($_GET);
    $catalogQuery = [
        'search' => $catalogView['filters']['search'] !== '' ? $catalogView['filters']['search'] : null,
        'category' => $catalogView['filters']['category'] !== '' ? $catalogView['filters']['category'] : null,
        'sort' => $catalogView['filters']['sort'] !== 'featured' ? $catalogView['filters']['sort'] : null,
        'min_price' => $catalogView['filters']['min_price'],
        'max_price' => $catalogView['filters']['max_price'],
        'in_stock' => $catalogView['filters']['in_stock'] ? '1' : null,
    ];

    $buildCatalogUrl = static function (array $overrides = []) use ($catalogQuery): string {
        $params = array_merge($catalogQuery, $overrides);

        foreach ($params as $key => $value) {
            if ($value === null || $value === '' || $value === false) {
                unset($params[$key]);
            }
        }

        $query = http_build_query($params);

        return '/' . ($query !== '' ? '?' . $query : '') . '#catalog-feed';
    };

    $showSidebarFilters = $catalogView['filters']['search'] !== ''
        || $catalogView['filters']['category'] !== ''
        || $catalogView['filters']['min_price'] !== null
        || $catalogView['filters']['max_price'] !== null
        || $catalogView['filters']['in_stock'];

    $categoryShortcuts = array_slice($catalogView['categories'], 0, 6);

    echo json_encode([
        'ok' => true,
        'count' => count($catalogView['products']),
        'summary' => count($catalogView['products']) . ' products available',
        'active_filter_count' => $catalogView['activeFilterCount'],
        'show_sidebar_filters' => $showSidebarFilters,
        'filters' => $catalogView['filters'],
        'metrics' => [
            'products_shown' => count($catalogView['products']),
            'categories' => count($catalogView['categories']),
        ],
        'sidebar_html' => $showSidebarFilters
            ? render('partials/catalog-sidebar', [
                'catalogView' => $catalogView,
                'buildCatalogUrl' => $buildCatalogUrl,
            ])
            : '',
        'shortcuts_html' => render('partials/catalog-shortcuts', ['categories' => $categoryShortcuts]),
        'featured_html' => render('partials/category-showcase', ['categories' => $catalogView['categories']]),
        'results_html' => render('partials/catalog-grid', ['products' => $catalogView['products']]),
        'search_suggestions' => [
            'products' => array_values(array_map(
                static fn(array $product): array => [
                    'name' => (string) $product['name'],
                    'category' => (string) ($product['category_name'] ?? ''),
                    'seller' => (string) ($product['seller_name'] ?? ''),
                ],
                $catalogView['products']
            )),
            'categories' => array_values(array_map(
                static fn(array $category): array => [
                    'name' => (string) ($category['name'] ?? ''),
                    'slug' => (string) ($category['slug'] ?? ''),
                    'product_count' => (int) ($category['product_count'] ?? 0),
                ],
                $catalogView['categories']
            )),
        ],
    ]);
} catch (\Throwable $exception) {
    report_exception($exception, 'api.products');
    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'message' => 'Unable to load products right now. Please try again shortly.',
    ]);
}
