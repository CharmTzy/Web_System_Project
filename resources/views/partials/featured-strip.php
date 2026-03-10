<?php

declare(strict_types=1);
?>
<?php foreach ($products as $product): ?>
    <article class="deal-card">
        <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
        <div class="deal-card__body">
            <span class="pill-badge pill-badge--soft"><?= e($product['category_name']) ?></span>
            <h3><?= e($product['name']) ?></h3>
            <p><?= e($product['short_description']) ?></p>
            <div class="deal-card__footer">
                <strong><?= e(money($product['price'])) ?></strong>
                <span><?= e((string) $product['stock_quantity']) ?> left</span>
            </div>
        </div>
    </article>
<?php endforeach; ?>
