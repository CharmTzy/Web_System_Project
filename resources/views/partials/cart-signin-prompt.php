<?php

declare(strict_types=1);

$compact = (bool) ($compact ?? false);
$loginUrl = (string) ($login_url ?? '/login.php?redirect=%2Fcart.html&cart_notice=full-cart');
$title = (string) ($title ?? 'Sign in to view your cart.');
$copy = (string) ($copy ?? 'Save items to your account, review your cart preview, and continue to checkout after signing in.');
$ctaLabel = (string) ($cta_label ?? 'Go to sign in');
?>
<div class="empty-state<?= $compact ? ' empty-state--compact' : '' ?>">
    <h3><?= e($title) ?></h3>
    <p><?= e($copy) ?></p>
    <a class="btn btn-brand" href="<?= e($loginUrl) ?>"><?= e($ctaLabel) ?></a>
</div>
