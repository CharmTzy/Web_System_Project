<?php

declare(strict_types=1);
?>
<?php if ($products === []): ?>
    <div class="empty-state">
        <h3>No products matched those filters.</h3>
        <p>Try a different category, lower the minimum price, or clear the search to widen the results.</p>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($products as $product): ?>
            <div class="col-sm-6 col-xl-4">
                <?= render('partials/product-card', ['product' => $product]) ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

