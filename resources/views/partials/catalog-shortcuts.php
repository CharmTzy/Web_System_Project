<?php

declare(strict_types=1);
?>
<?php foreach ($categories as $category): ?>
    <a class="shortcut-tile" href="/index.html?category=<?= e($category['slug']) ?>" data-shortcut-link>
        <span class="shortcut-tile__icon"><?= e(strtoupper(substr($category['name'], 0, 1))) ?></span>
        <span class="shortcut-tile__title"><?= e($category['name']) ?></span>
        <span class="shortcut-tile__meta"><?= e((string) $category['product_count']) ?> products</span>
    </a>
<?php endforeach; ?>
