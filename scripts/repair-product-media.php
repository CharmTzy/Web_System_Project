#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Repositories\ProductRepository;
use App\Support\Database;

$config = require dirname(__DIR__) . '/bootstrap.php';

require_once dirname(__DIR__) . '/app/Support/Database.php';

$database = new Database($config['database']);
$connection = $database->connection();

if (!$connection instanceof PDO) {
    fwrite(STDERR, "Unable to connect to the NovaMarket database.\n");
    exit(1);
}

$repository = new ProductRepository($connection);
$options = parse_cli_options($argv);
$apply = $options['apply'];
$productFilter = $options['product'];
$bucket = google_cloud_storage_bucket($config['app']);
$prefix = google_cloud_storage_product_prefix($config['app']);

if ($bucket === '') {
    fwrite(STDERR, "GOOGLE_CLOUD_STORAGE_BUCKET is not configured.\n");
    exit(1);
}

$products = $repository->listManagedProducts();

if ($productFilter !== null) {
    $products = array_values(array_filter(
        $products,
        static fn (array $product): bool => (string) $product['slug'] === $productFilter || (string) $product['id'] === $productFilter
    ));
}

if ($products === []) {
    fwrite(STDOUT, "No matching products found.\n");
    exit(0);
}

try {
    $bucketObjects = fetch_bucket_objects($bucket, $prefix);
} catch (RuntimeException $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}

$summary = [
    'matched' => 0,
    'placeholders' => 0,
    'skipped_manual' => 0,
    'unchanged' => 0,
    'updated' => 0,
];

foreach ($products as $product) {
    $productId = (int) $product['id'];
    $productName = (string) $product['name'];
    $productSlug = (string) $product['slug'];
    $productCategory = (string) $product['category_name'];
    $productSku = (string) $product['sku'];
    $managed = $repository->findManagedById($productId) ?? $product;
    $currentMedia = is_array($managed['media'] ?? null) ? $managed['media'] : [];

    if (has_manual_upload_media($bucket, $prefix, $productId, $currentMedia)) {
        $summary['skipped_manual']++;
        fwrite(STDOUT, sprintf("[skip] %s (%s) keeps its product-specific uploads.\n", $productName, $productSlug));
        continue;
    }

    $matchedImages = matched_bucket_image_urls($bucket, $prefix, $productSlug, $bucketObjects);
    $desiredImageUrls = $matchedImages;
    $sourceLabel = 'bucket';

    if ($desiredImageUrls === []) {
        $sourceLabel = 'generated';
        $desiredImageUrls = placeholder_public_urls($bucket, $prefix, $productSlug);
    }

    $currentImageUrls = current_image_urls($currentMedia);

    if ($currentImageUrls === $desiredImageUrls && (string) ($managed['image_url'] ?? '') === ($desiredImageUrls[0] ?? '')) {
        $summary['unchanged']++;
        fwrite(STDOUT, sprintf("[ok] %s already uses the correct %s media.\n", $productName, $sourceLabel));
        continue;
    }

    if (!$apply) {
        fwrite(
            STDOUT,
            sprintf(
                "[dry-run] %s -> %s (%d image%s)\n",
                $productName,
                $sourceLabel,
                count($desiredImageUrls),
                count($desiredImageUrls) === 1 ? '' : 's'
            )
        );
        continue;
    }

    if ($sourceLabel === 'generated') {
        $desiredImageUrls = upload_placeholder_gallery(
            $bucket,
            $prefix,
            $productSlug,
            $productName,
            $productCategory,
            $productSku
        );
        $summary['placeholders']++;
    } else {
        $summary['matched']++;
    }

    sync_product_images($connection, $repository, $productId, $productName, $desiredImageUrls);
    $summary['updated']++;

    fwrite(
        STDOUT,
        sprintf(
            "[updated] %s now uses %d %s image%s.\n",
            $productName,
            count($desiredImageUrls),
            $sourceLabel,
            count($desiredImageUrls) === 1 ? '' : 's'
        )
    );
}

fwrite(STDOUT, "\nSummary\n");
fwrite(STDOUT, "-------\n");
fwrite(STDOUT, sprintf("Updated: %d\n", $summary['updated']));
fwrite(STDOUT, sprintf("Matched existing cloud galleries: %d\n", $summary['matched']));
fwrite(STDOUT, sprintf("Generated cloud fallback galleries: %d\n", $summary['placeholders']));
fwrite(STDOUT, sprintf("Skipped manual uploads: %d\n", $summary['skipped_manual']));
fwrite(STDOUT, sprintf("Already correct: %d\n", $summary['unchanged']));
fwrite(
    STDOUT,
    $apply
        ? "Completed. Open a repaired product page and hard refresh to verify the updated gallery.\n"
        : "Run again with --apply on the Google Cloud VM to persist the repair.\n"
);

function parse_cli_options(array $argv): array
{
    $apply = in_array('--apply', $argv, true);
    $product = null;

    foreach ($argv as $argument) {
        if (!str_starts_with($argument, '--product=')) {
            continue;
        }

        $value = trim(substr($argument, strlen('--product=')));

        if ($value !== '') {
            $product = $value;
        }
    }

    return [
        'apply' => $apply,
        'product' => $product,
    ];
}

function fetch_bucket_objects(string $bucket, string $prefix): array
{
    $objects = [];
    $pageToken = null;

    do {
        $url = sprintf(
            'https://storage.googleapis.com/storage/v1/b/%s/o?prefix=%s/&maxResults=1000%s',
            rawurlencode($bucket),
            rawurlencode(trim($prefix, '/')),
            $pageToken !== null ? '&pageToken=' . rawurlencode($pageToken) : ''
        );

        $response = @file_get_contents($url);

        if ($response === false) {
            throw new RuntimeException('Unable to read the public Google Cloud Storage object listing.');
        }

        $payload = json_decode($response, true);

        if (!is_array($payload)) {
            throw new RuntimeException('Google Cloud Storage returned an unreadable object listing.');
        }

        foreach ($payload['items'] ?? [] as $item) {
            $name = trim((string) ($item['name'] ?? ''));

            if ($name !== '') {
                $objects[] = $name;
            }
        }

        $pageToken = isset($payload['nextPageToken']) ? trim((string) $payload['nextPageToken']) : '';
        $pageToken = $pageToken !== '' ? $pageToken : null;
    } while ($pageToken !== null);

    return array_values(array_unique($objects));
}

function has_manual_upload_media(string $bucket, string $prefix, int $productId, array $media): bool
{
    $managedPrefix = trim($prefix, '/') . '/product-' . $productId . '/';

    foreach ($media as $item) {
        $url = (string) ($item['url'] ?? '');
        $objectPath = google_cloud_storage_public_url_path($bucket, $url);

        if ($objectPath !== null && str_starts_with($objectPath, $managedPrefix)) {
            return true;
        }
    }

    return false;
}

function matched_bucket_image_urls(string $bucket, string $prefix, string $slug, array $bucketObjects): array
{
    $rootPrefix = trim($prefix, '/') . '/';
    $matches = [];

    foreach ($bucketObjects as $objectPath) {
        if (!str_starts_with($objectPath, $rootPrefix)) {
            continue;
        }

        $relative = substr($objectPath, strlen($rootPrefix));

        if ($relative === false || $relative === '' || str_contains($relative, '/')) {
            continue;
        }

        if (str_contains($relative, '-placeholder-')) {
            continue;
        }

        if (!preg_match('/\.(jpg|jpeg|png|webp|gif|avif)$/i', $relative)) {
            continue;
        }

        if (!str_starts_with($relative, $slug)) {
            continue;
        }

        $nameWithoutExtension = preg_replace('/\.[^.]+$/', '', $relative);

        if ($nameWithoutExtension === null) {
            continue;
        }

        if (
            $nameWithoutExtension !== $slug
            && !preg_match('/^' . preg_quote($slug, '/') . '-\d+$/', $nameWithoutExtension)
        ) {
            continue;
        }

        $matches[] = google_cloud_storage_public_url($bucket, $objectPath);
    }

    usort($matches, static fn (string $left, string $right): int => strnatcasecmp($left, $right));

    $numbered = array_values(array_filter(
        $matches,
        static fn (string $url): bool => (bool) preg_match('/-\d+\.(jpg|jpeg|png|webp|gif|avif)$/i', $url)
    ));

    return $numbered !== [] ? $numbered : $matches;
}

function current_image_urls(array $media): array
{
    $urls = [];

    foreach ($media as $item) {
        if (($item['type'] ?? 'image') !== 'image') {
            continue;
        }

        $url = trim((string) ($item['url'] ?? ''));

        if ($url !== '') {
            $urls[] = $url;
        }
    }

    return $urls;
}

function placeholder_public_urls(string $bucket, string $prefix, string $slug): array
{
    $urls = [];

    for ($variant = 1; $variant <= 3; $variant++) {
        $urls[] = google_cloud_storage_public_url(
            $bucket,
            sprintf('%s/generated/%s-placeholder-%d.svg', trim($prefix, '/'), $slug, $variant)
        );
    }

    return $urls;
}

function upload_placeholder_gallery(
    string $bucket,
    string $prefix,
    string $slug,
    string $productName,
    string $categoryName,
    string $sku
): array {
    $urls = [];

    for ($variant = 1; $variant <= 3; $variant++) {
        $svg = render_placeholder_svg($slug, $productName, $categoryName, $sku, $variant);
        $tempPath = tempnam(sys_get_temp_dir(), 'nm-product-media-');

        if ($tempPath === false) {
            throw new RuntimeException('Unable to allocate a temporary file for generated product media.');
        }

        if (file_put_contents($tempPath, $svg) === false) {
            @unlink($tempPath);
            throw new RuntimeException('Unable to write the generated product media placeholder.');
        }

        try {
            $urls[] = google_cloud_storage_upload_object(
                $bucket,
                sprintf('%s/generated/%s-placeholder-%d.svg', trim($prefix, '/'), $slug, $variant),
                $tempPath,
                'image/svg+xml'
            );
        } finally {
            @unlink($tempPath);
        }
    }

    return $urls;
}

function sync_product_images(
    PDO $connection,
    ProductRepository $repository,
    int $productId,
    string $productName,
    array $imageUrls
): void {
    if ($imageUrls === []) {
        return;
    }

    $connection->beginTransaction();

    try {
        $connection->prepare('UPDATE product_media SET is_primary = 0 WHERE product_id = :product_id')
            ->execute(['product_id' => $productId]);

        $connection->prepare('DELETE FROM product_media WHERE product_id = :product_id AND media_type = :media_type')
            ->execute([
                'product_id' => $productId,
                'media_type' => 'image',
            ]);

        foreach (array_values($imageUrls) as $index => $url) {
            $repository->createProductMedia($productId, [
                'media_type' => 'image',
                'media_url' => $url,
                'thumbnail_url' => $url,
                'alt_text' => $productName,
                'sort_order' => $index + 1,
                'is_primary' => $index === 0,
            ]);
        }

        $repository->syncProductImageUrlFromMedia($productId, $imageUrls[0]);
        $connection->commit();
    } catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }

        throw $exception;
    }
}

function render_placeholder_svg(
    string $slug,
    string $productName,
    string $categoryName,
    string $sku,
    int $variant
): string {
    $palettes = [
        ['#0F2D52', '#2F6FDB', '#DCE9FF'],
        ['#143F39', '#33A370', '#D9FFF1'],
        ['#4A2815', '#D27C43', '#FFE7D6'],
        ['#36225F', '#8064E8', '#EEE7FF'],
        ['#3D2430', '#D56794', '#FFE2EE'],
    ];
    $palette = $palettes[(int) (abs(crc32($slug . ':' . $variant)) % count($palettes))];
    [$dark, $accent, $soft] = $palette;
    $lines = wrap_product_name($productName);
    $titleY = 700 - (count($lines) * 70);
    $escapedTitle = implode(
        '',
        array_map(
            static fn (string $line, int $index): string => sprintf(
                '<tspan x="140" dy="%d">%s</tspan>',
                $index === 0 ? 0 : 78,
                svg_escape($line)
            ),
            $lines,
            array_keys($lines)
        )
    );

    $decorations = match ($variant) {
        1 => sprintf(
            '<circle cx="1260" cy="330" r="210" fill="%1$s" opacity="0.18"/><circle cx="1160" cy="430" r="120" fill="%2$s" opacity="0.22"/><rect x="120" y="220" width="440" height="24" rx="12" fill="%2$s" opacity="0.38"/>',
            $soft,
            $accent
        ),
        2 => sprintf(
            '<rect x="980" y="180" width="340" height="340" rx="72" fill="%1$s" opacity="0.18"/><rect x="1040" y="240" width="220" height="220" rx="48" fill="%2$s" opacity="0.28"/><rect x="128" y="240" width="320" height="20" rx="10" fill="%2$s" opacity="0.38"/>',
            $soft,
            $accent
        ),
        default => sprintf(
            '<path d="M1110 180C1230 180 1340 250 1380 360C1410 450 1390 570 1290 630C1190 690 1040 670 960 580C880 490 900 350 980 270C1020 220 1070 180 1110 180Z" fill="%1$s" opacity="0.18"/><path d="M1070 260C1150 260 1230 310 1250 390C1260 450 1230 530 1160 570C1080 620 960 590 910 520C870 460 870 360 940 300C980 270 1020 260 1070 260Z" fill="%2$s" opacity="0.26"/>',
            $soft,
            $accent
        ),
    };

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="1600" viewBox="0 0 1600 1600" fill="none">
  <defs>
    <linearGradient id="bg-{$variant}" x1="160" y1="120" x2="1440" y2="1480" gradientUnits="userSpaceOnUse">
      <stop stop-color="{$dark}"/>
      <stop offset="1" stop-color="{$accent}"/>
    </linearGradient>
    <linearGradient id="card-{$variant}" x1="140" y1="180" x2="960" y2="1320" gradientUnits="userSpaceOnUse">
      <stop stop-color="rgba(255,255,255,0.30)"/>
      <stop offset="1" stop-color="rgba(255,255,255,0.10)"/>
    </linearGradient>
  </defs>
  <rect width="1600" height="1600" rx="80" fill="url(#bg-{$variant})"/>
  <rect x="96" y="96" width="1408" height="1408" rx="64" fill="white" fill-opacity="0.08" stroke="white" stroke-opacity="0.18"/>
  <rect x="120" y="180" width="820" height="1140" rx="56" fill="white" fill-opacity="0.10" stroke="white" stroke-opacity="0.16"/>
  {$decorations}
  <text x="140" y="360" fill="white" font-size="44" font-family="Inter, Arial, sans-serif" font-weight="700" letter-spacing="4">NOVAMARKET</text>
  <text x="140" y="420" fill="{$soft}" font-size="28" font-family="Inter, Arial, sans-serif" font-weight="600" letter-spacing="2">{$categoryName}</text>
  <text x="140" y="{$titleY}" fill="white" font-size="86" font-family="Inter, Arial, sans-serif" font-weight="800">{$escapedTitle}</text>
  <text x="140" y="1080" fill="{$soft}" font-size="30" font-family="Inter, Arial, sans-serif" font-weight="500">Dedicated gallery asset</text>
  <text x="140" y="1140" fill="white" fill-opacity="0.82" font-size="34" font-family="Inter, Arial, sans-serif" font-weight="600">{$sku}</text>
  <rect x="140" y="1210" width="268" height="82" rx="41" fill="white" fill-opacity="0.16" stroke="white" stroke-opacity="0.22"/>
  <text x="188" y="1260" fill="white" font-size="28" font-family="Inter, Arial, sans-serif" font-weight="700">Variant {$variant}</text>
  <text x="980" y="1260" fill="white" fill-opacity="0.72" font-size="26" font-family="Inter, Arial, sans-serif" text-anchor="end">Generated to replace mismatched legacy media</text>
</svg>
SVG;
}

function wrap_product_name(string $name): array
{
    $words = preg_split('/\s+/', trim($name)) ?: [$name];
    $lines = [];
    $current = '';

    foreach ($words as $word) {
        $candidate = trim($current . ' ' . $word);

        if ($current !== '' && strlen($candidate) > 18) {
            $lines[] = $current;
            $current = $word;
            continue;
        }

        $current = $candidate;
    }

    if ($current !== '') {
        $lines[] = $current;
    }

    return array_slice($lines, 0, 3);
}

function svg_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}
