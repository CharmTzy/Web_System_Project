<?php

declare(strict_types=1);

$pagination = $pagination ?? null;

if (!is_array($pagination) || (int) ($pagination['total_pages'] ?? 1) <= 1) {
    return;
}

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$query = $_GET;
unset($query['page']);

$buildUrl = static function (int $page) use ($currentPath, $query): string {
    $params = $query;

    if ($page > 1) {
        $params['page'] = $page;
    }

    $queryString = http_build_query($params);

    return $currentPath . ($queryString !== '' ? '?' . $queryString : '');
};

$windowStart = max(1, (int) $pagination['page'] - 2);
$windowEnd = min((int) $pagination['total_pages'], (int) $pagination['page'] + 2);
?>
<nav class="admin-pagination" aria-label="Table pagination">
    <p class="admin-pagination__summary">
        Showing <?= e((string) $pagination['from']) ?>-<?= e((string) $pagination['to']) ?>
        of <?= e((string) $pagination['total_items']) ?>
    </p>
    <div class="admin-pagination__links">
        <?php if (!empty($pagination['has_prev'])): ?>
            <a class="admin-pagination__control" href="<?= e($buildUrl((int) $pagination['prev_page'])) ?>">Previous</a>
        <?php endif; ?>

        <?php for ($pageNumber = $windowStart; $pageNumber <= $windowEnd; $pageNumber++): ?>
            <a
                class="admin-pagination__page<?= $pageNumber === (int) $pagination['page'] ? ' is-active' : '' ?>"
                href="<?= e($buildUrl($pageNumber)) ?>"
                <?= $pageNumber === (int) $pagination['page'] ? 'aria-current="page"' : '' ?>
            >
                <?= e((string) $pageNumber) ?>
            </a>
        <?php endfor; ?>

        <?php if (!empty($pagination['has_next'])): ?>
            <a class="admin-pagination__control" href="<?= e($buildUrl((int) $pagination['next_page'])) ?>">Next</a>
        <?php endif; ?>
    </div>
</nav>
