<?php

declare(strict_types=1);

$appName = $appName ?? 'NovaMarket';
$pageTitle = $pageTitle ?? 'Account';
$pageScript = $pageScript ?? null;
$bodyClass = $bodyClass ?? 'auth-page';
$bodyClass = trim($bodyClass . ' page-loading');
$authFormView = $authFormView ?? '';
$pageSkeletonVariant = $pageSkeletonVariant ?? 'auth';
$authPage = $authPage ?? [];
$robotsMeta = trim((string) ($robotsMeta ?? 'noindex, nofollow, noarchive'));

$highlights = is_array($authPage['highlights'] ?? null) ? $authPage['highlights'] : [];
$utilityLinks = is_array($authPage['utility_links'] ?? null) ? $authPage['utility_links'] : [
    ['label' => 'Shop', 'href' => '/'],
    ['label' => 'Cart', 'href' => '/login.php?redirect=%2Fcart.html&cart_notice=full-cart'],
];
$contextNotice = is_array($authPage['context_notice'] ?? null) ? $authPage['context_notice'] : [];
$modalNotice = is_array($authPage['modal_notice'] ?? null) ? $authPage['modal_notice'] : [];

if ($robotsMeta !== '' && !headers_sent()) {
    header('X-Robots-Tag: ' . $robotsMeta);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <?php if ($robotsMeta !== ''): ?>
        <meta name="robots" content="<?= e($robotsMeta) ?>">
    <?php endif; ?>
    <title><?= e($pageTitle) ?> | <?= e($appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&amp;family=Plus+Jakarta+Sans:wght@500;600;700;800&amp;display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/auth.css')) ?>">
</head>

<body class="<?= e($bodyClass) ?>">
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <?= render('partials/page-skeleton', ['variant' => $pageSkeletonVariant]) ?>
    <script>
        (() => {
            const minimumDelay = 900;
            const startedAt = window.performance?.now?.() ?? Date.now();
            const maximumFontWait = 1600;
            let revealScheduled = false;
            let revealPromise = null;

            const revealPage = () => {
                const body = document.body;

                if (!body) {
                    return;
                }

                body.classList.remove('page-loading');
                body.classList.add('page-ready');

                window.setTimeout(() => {
                    document.querySelectorAll('[data-page-skeleton]').forEach((node) => node.remove());
                }, 320);
            };

            const scheduleReveal = () => {
                if (revealScheduled) {
                    return revealPromise;
                }

                revealScheduled = true;

                const now = window.performance?.now?.() ?? Date.now();
                const remaining = Math.max(0, minimumDelay - (now - startedAt));
                const delayGate = new Promise((resolve) => {
                    window.setTimeout(resolve, remaining);
                });
                const fontGate = (() => {
                    if (!document.fonts?.ready) {
                        return Promise.resolve();
                    }

                    return Promise.race([
                        document.fonts.ready.catch(() => undefined),
                        new Promise((resolve) => window.setTimeout(resolve, maximumFontWait)),
                    ]);
                })();

                revealPromise = Promise.all([delayGate, fontGate]).then(revealPage);

                return revealPromise;
            };

            window.__novaRevealPageShell = revealPage;

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', scheduleReveal, { once: true });
            } else {
                scheduleReveal();
            }

            window.addEventListener('load', scheduleReveal, { once: true });
        })();
    </script>
    <main id="main-content" class="auth-page__main" tabindex="-1">
        <div class="container auth-page__container">
            <section class="auth-shell">
                <div class="auth-spotlight">
                    <a class="auth-brand" href="/" aria-label="NovaMarket home">
                        <span class="auth-brand__mark">NM</span>
                        <span class="auth-brand__wordmark"><?= e($appName) ?></span>
                    </a>

                    <div class="auth-spotlight__content">
                        <?php if (!empty($authPage['eyebrow'])): ?>
                            <span class="auth-spotlight__eyebrow"><?= e((string) $authPage['eyebrow']) ?></span>
                        <?php endif; ?>
                        <h1><?= e((string) ($authPage['title'] ?? 'Welcome to NovaMarket.')) ?></h1>
                        <p><?= e((string) ($authPage['copy'] ?? 'Access your account and continue browsing the latest finds.')) ?>
                        </p>

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
                                    <a
                                        href="<?= e((string) ($link['href'] ?? '/')) ?>"><?= e((string) ($link['label'] ?? 'Explore')) ?></a>
                                <?php endforeach; ?>
                            </nav>
                        <?php endif; ?>

                        <div class="auth-spotlight__cta">
                            <strong><?= e((string) ($authPage['banner_title'] ?? 'Take a look around first')) ?></strong>
                            <p><?= e((string) ($authPage['banner_copy'] ?? 'You can browse the storefront any time before signing in.')) ?>
                            </p>
                            <a class="auth-spotlight__cta-link"
                                href="<?= e((string) ($authPage['cta_href'] ?? '/')) ?>">
                                <?= e((string) ($authPage['cta_label'] ?? 'Go to storefront')) ?>
                            </a>
                        </div>

                        <?php if ($contextNotice !== []): ?>
                            <div class="auth-spotlight__context-note" <?php if (!empty($contextNotice['id'])): ?>id="<?= e((string) $contextNotice['id']) ?>" <?php endif; ?> hidden>
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

    <?php if ($modalNotice !== []): ?>
        <div class="auth-modal is-visible" data-auth-modal>
            <div class="auth-modal__backdrop" data-auth-modal-close></div>
            <div class="auth-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="authModalTitle">
                <button class="auth-modal__close" type="button" data-auth-modal-close
                    aria-label="Close notice">&times;</button>
                <span class="auth-modal__eyebrow">Cart access</span>
                <h2 id="authModalTitle"><?= e((string) ($modalNotice['title'] ?? '')) ?></h2>
                <p><?= e((string) ($modalNotice['copy'] ?? '')) ?></p>
                <button class="btn btn-brand w-100" type="button" data-auth-modal-close>
                    <?= e((string) ($modalNotice['button_label'] ?? 'Continue')) ?>
                </button>
            </div>
        </div>
        <script>
            (() => {
                const modal = document.querySelector('[data-auth-modal]');

                if (!modal) {
                    return;
                }

                const closeModal = () => {
                    modal.classList.remove('is-visible');
                    document.body.classList.remove('auth-modal-open');
                    document.getElementById('login-email')?.focus();
                };

                document.body.classList.add('auth-modal-open');

                modal.querySelectorAll('[data-auth-modal-close]').forEach((node) => {
                    node.addEventListener('click', closeModal);
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && modal.classList.contains('is-visible')) {
                        closeModal();
                    }
                });
            })();
        </script>
    <?php endif; ?>

    <?php if (!empty($pageScript)): ?>
        <script src="<?= e(asset('js/' . $pageScript)) ?>"></script>
    <?php endif; ?>
</body>

</html>
