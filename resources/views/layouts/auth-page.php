<?php

declare(strict_types=1);

$appName = $appName ?? 'NovaMarket';
$pageTitle = $pageTitle ?? 'Account';
$pageScript = $pageScript ?? null;
$bodyClass = $bodyClass ?? 'auth-page';
$authFormView = $authFormView ?? '';
$authPage = $authPage ?? [];

$highlights = is_array($authPage['highlights'] ?? null) ? $authPage['highlights'] : [];
$utilityLinks = is_array($authPage['utility_links'] ?? null) ? $authPage['utility_links'] : [
    ['label' => 'Shop', 'href' => '/index.html'],
    ['label' => 'Cart', 'href' => '/cart.html'],
];
$contextNotice = is_array($authPage['context_notice'] ?? null) ? $authPage['context_notice'] : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle) ?> | <?= e($appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&amp;family=Plus+Jakarta+Sans:wght@500;600;700;800&amp;display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
    <main class="auth-page__main">
        <div class="container auth-page__container">
            <section class="auth-shell">
                <div class="auth-spotlight">
                    <a class="auth-brand" href="/index.html" aria-label="NovaMarket home">
                        <span class="auth-brand__mark">NM</span>
                        <span class="auth-brand__wordmark"><?= e($appName) ?></span>
                    </a>

                    <div class="auth-spotlight__content">
                        <?php if (!empty($authPage['eyebrow'])): ?>
                            <span class="auth-spotlight__eyebrow"><?= e((string) $authPage['eyebrow']) ?></span>
                        <?php endif; ?>
                        <h1><?= e((string) ($authPage['title'] ?? 'Welcome to NovaMarket.')) ?></h1>
                        <p><?= e((string) ($authPage['copy'] ?? 'Access your account and continue browsing the latest finds.')) ?></p>

                        <?php if ($highlights !== []): ?>
                            <div class="auth-spotlight__highlights">
                                <?php foreach ($highlights as $highlight): ?>
                                    <article class="auth-spotlight__card">
                                        <strong><?= e((string) ($highlight['title'] ?? '')) ?></strong>
                                        <span><?= e((string) ($highlight['copy'] ?? '')) ?></span>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="auth-spotlight__footer">
                        <?php if ($utilityLinks !== []): ?>
                            <nav class="auth-spotlight__quicklinks" aria-label="Quick links">
                                <?php foreach ($utilityLinks as $link): ?>
                                    <a href="<?= e((string) ($link['href'] ?? '/index.html')) ?>"><?= e((string) ($link['label'] ?? 'Explore')) ?></a>
                                <?php endforeach; ?>
                            </nav>
                        <?php endif; ?>

                        <div class="auth-spotlight__cta">
                            <strong><?= e((string) ($authPage['banner_title'] ?? 'Take a look around first')) ?></strong>
                            <p><?= e((string) ($authPage['banner_copy'] ?? 'You can browse the storefront any time before signing in.')) ?></p>
                            <a class="auth-spotlight__cta-link" href="<?= e((string) ($authPage['cta_href'] ?? '/index.html')) ?>">
                                <?= e((string) ($authPage['cta_label'] ?? 'Go to storefront')) ?>
                            </a>
                        </div>

                        <?php if ($contextNotice !== []): ?>
                            <div
                                class="auth-spotlight__context-note"
                                <?php if (!empty($contextNotice['id'])): ?>id="<?= e((string) $contextNotice['id']) ?>"<?php endif; ?>
                                hidden
                            >
                                <?php if (!empty($contextNotice['title'])): ?>
                                    <strong><?= e((string) $contextNotice['title']) ?></strong>
                                <?php endif; ?>
                                <?php if (!empty($contextNotice['copy'])): ?>
                                    <p><?= e((string) $contextNotice['copy']) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="auth-panel">
                    <?= render($authFormView) ?>
                </div>
            </section>

            <p class="auth-page__footnote">Copyright &copy; 2026 <?= e($appName) ?>. All rights reserved.</p>
        </div>
    </main>

    <?php if (!empty($pageScript)): ?>
        <script src="<?= e(asset('js/' . $pageScript)) ?>"></script>
    <?php endif; ?>
</body>
</html>
