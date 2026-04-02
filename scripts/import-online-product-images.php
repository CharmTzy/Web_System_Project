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

$bucket = google_cloud_storage_bucket($config['app']);
$prefix = google_cloud_storage_product_prefix($config['app']);

if ($bucket === '') {
    fwrite(STDERR, "GOOGLE_CLOUD_STORAGE_BUCKET is not configured.\n");
    exit(1);
}

$repository = new ProductRepository($connection);
$options = parse_cli_options($argv);
$apply = $options['apply'];
$productFilter = $options['product'];

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

$summary = [
    'updated' => 0,
    'skipped_manual' => 0,
    'skipped_existing' => 0,
    'missing_query' => 0,
    'missing_result' => 0,
];

foreach ($products as $product) {
    $productId = (int) $product['id'];
    $productName = (string) $product['name'];
    $productSlug = (string) $product['slug'];
    $managed = $repository->findManagedById($productId) ?? $product;
    $currentMedia = is_array($managed['media'] ?? null) ? $managed['media'] : [];

    if (has_manual_upload_media($bucket, $prefix, $productId, $currentMedia)) {
        $summary['skipped_manual']++;
        fwrite(STDOUT, sprintf("[skip] %s keeps product-specific uploaded media.\n", $productName));
        continue;
    }

    if (has_slug_matched_media($bucket, $prefix, $productSlug, $currentMedia)) {
        $summary['skipped_existing']++;
        fwrite(STDOUT, sprintf("[skip] %s already has a slug-matched product image.\n", $productName));
        continue;
    }

    $query = online_image_query_map()[$productSlug] ?? null;

    if ($query === null) {
        $summary['missing_query']++;
        fwrite(STDOUT, sprintf("[skip] %s has no online search query configured.\n", $productName));
        continue;
    }

    $candidate = fetch_openverse_image($query);

    if ($candidate === null) {
        $summary['missing_result']++;
        fwrite(STDOUT, sprintf("[skip] %s returned no Openverse image for query \"%s\".\n", $productName, $query));
        continue;
    }

    if (!$apply) {
        fwrite(
            STDOUT,
            sprintf(
                "[dry-run] %s <- %s (%s)\n",
                $productName,
                $candidate['title'] !== '' ? $candidate['title'] : 'Untitled image',
                $candidate['url']
            )
        );
        continue;
    }

    $download = download_image_to_temp($candidate['url']);
    $objectPath = sprintf('%s/%s-1.%s', trim($prefix, '/'), $productSlug, $download['extension']);
    $publicUrl = google_cloud_storage_upload_object(
        $bucket,
        $objectPath,
        $download['path'],
        $download['content_type']
    );

    @unlink($download['path']);

    sync_single_product_image($connection, $repository, $productId, $productName, $publicUrl);
    $summary['updated']++;

    fwrite(
        STDOUT,
        sprintf(
            "[updated] %s now uses %s\n          source: %s\n",
            $productName,
            $publicUrl,
            $candidate['url']
        )
    );
}

fwrite(STDOUT, "\nSummary\n");
fwrite(STDOUT, "-------\n");
fwrite(STDOUT, sprintf("Updated: %d\n", $summary['updated']));
fwrite(STDOUT, sprintf("Skipped manual uploads: %d\n", $summary['skipped_manual']));
fwrite(STDOUT, sprintf("Skipped existing slug images: %d\n", $summary['skipped_existing']));
fwrite(STDOUT, sprintf("Missing configured query: %d\n", $summary['missing_query']));
fwrite(STDOUT, sprintf("No Openverse result: %d\n", $summary['missing_result']));
fwrite(
    STDOUT,
    $apply
        ? "Completed. Hard refresh a repaired product page to see the new cloud image.\n"
        : "Run again with --apply on the Google Cloud VM to import and upload the images.\n"
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

function online_image_query_map(): array
{
    return [
        'orbit-usbc-hub' => 'usb c hub',
        'clouddesk-laptop-stand' => 'laptop stand',
        'linen-desk-pad' => 'desk pad',
        'arc-seat-cushion' => 'chair cushion',
        'brewmate-pour-over-kettle' => 'pour over kettle',
        'drift-ceramic-storage-jars' => 'kitchen canister',
        'calm-sleep-mask' => 'sleeping mask',
        'nourish-glass-tea-bottle' => 'glass water bottle',
        'metro-sling-pack' => 'messenger bag',
        'porter-tech-pouch' => 'travel pouch',
        'studio-monitor-light-bar' => 'desk lamp setup',
        'haven-reed-diffuser-set' => 'reed diffuser',
        'grove-storage-basket-set' => 'storage basket',
        'airlite-mini-fan' => 'portable fan',
        'cedar-knife-set' => 'chef knife',
        'atlas-packing-cube-set' => 'packing cubes',
        'shoreline-key-organizer' => 'key holder',
        'focus-noise-machine' => 'white noise machine',
        'glow-desk-clock' => 'desk clock',
        'cove-soy-candle-trio' => 'scented candle',
        'rapidcharge-power-bank' => 'power bank',
        'traveler-passport-wallet' => 'passport wallet',
    ];
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

function has_slug_matched_media(string $bucket, string $prefix, string $slug, array $media): bool
{
    $slugPrefix = trim($prefix, '/') . '/' . $slug;

    foreach ($media as $item) {
        if (($item['type'] ?? 'image') !== 'image') {
            continue;
        }

        $url = (string) ($item['url'] ?? '');
        $objectPath = google_cloud_storage_public_url_path($bucket, $url);

        if ($objectPath !== null && str_starts_with($objectPath, $slugPrefix)) {
            return true;
        }
    }

    return false;
}

function fetch_openverse_image(string $query): ?array
{
    $url = 'https://api.openverse.org/v1/images/?' . http_build_query([
        'q' => $query,
        'page_size' => 1,
        'license_type' => 'commercial',
    ]);

    $response = http_get($url, [
        'Accept: application/json',
    ]);

    $payload = json_decode($response['body'], true);

    if (!is_array($payload) || !isset($payload['results'][0]) || !is_array($payload['results'][0])) {
        return null;
    }

    $result = $payload['results'][0];
    $imageUrl = trim((string) ($result['url'] ?? ''));

    if ($imageUrl === '') {
        return null;
    }

    return [
        'title' => trim((string) ($result['title'] ?? '')),
        'url' => $imageUrl,
    ];
}

function download_image_to_temp(string $url): array
{
    $response = http_get($url, []);
    $contentTypeHeader = '';

    foreach ($response['headers'] as $header) {
        if (!str_starts_with(strtolower($header), 'content-type:')) {
            continue;
        }

        $contentTypeHeader = trim(substr($header, strlen('content-type:')));
        break;
    }

    $contentType = strtolower(trim(explode(';', $contentTypeHeader)[0] ?? ''));

    $extensionMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/avif' => 'avif',
    ];

    if (!isset($extensionMap[$contentType])) {
        throw new RuntimeException('The selected online image is not a supported image format.');
    }

    $tempPath = tempnam(sys_get_temp_dir(), 'nm-online-product-');

    if ($tempPath === false || file_put_contents($tempPath, $response['body']) === false) {
        throw new RuntimeException('Unable to store the downloaded image temporarily.');
    }

    return [
        'path' => $tempPath,
        'content_type' => $contentType,
        'extension' => $extensionMap[$contentType],
    ];
}

function http_get(string $url, array $headers): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP cURL is required to import online product images.');
    }

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => array_merge([
            'User-Agent: NovaMarket Product Import/1.0',
        ], $headers),
        CURLOPT_HEADER => true,
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    if ($response === false || $status < 200 || $status >= 300) {
        throw new RuntimeException(
            $curlError !== ''
                ? $curlError
                : 'Unable to download the selected online product image.'
        );
    }

    $headerString = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    $headerLines = array_values(array_filter(array_map('trim', explode("\r\n", (string) $headerString))));

    return [
        'headers' => $headerLines,
        'body' => $body === false ? '' : $body,
    ];
}

function sync_single_product_image(
    PDO $connection,
    ProductRepository $repository,
    int $productId,
    string $productName,
    string $publicUrl
): void {
    $connection->beginTransaction();

    try {
        $connection->prepare('UPDATE product_media SET is_primary = 0 WHERE product_id = :product_id')
            ->execute(['product_id' => $productId]);

        $connection->prepare('DELETE FROM product_media WHERE product_id = :product_id AND media_type = :media_type')
            ->execute([
                'product_id' => $productId,
                'media_type' => 'image',
            ]);

        $repository->createProductMedia($productId, [
            'media_type' => 'image',
            'media_url' => $publicUrl,
            'thumbnail_url' => $publicUrl,
            'alt_text' => $productName,
            'sort_order' => 1,
            'is_primary' => true,
        ]);

        $repository->syncProductImageUrlFromMedia($productId, $publicUrl);
        $connection->commit();
    } catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }

        throw $exception;
    }
}
