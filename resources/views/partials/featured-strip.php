<?php

declare(strict_types=1);
?>
<?php foreach ($products as $product): ?>
    <a class="deal-card deal-card--market" href="/index.html?category=<?= e($product['category_slug']) ?>#catalog-feed" data-shortcut-link>
        <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
        <div class="deal-card__body">
            <h3><?= e($product['name']) ?></h3>
            <p><?= e($product['category_name']) ?></p>
        </div>
    </a>
<?php endforeach; ?>
