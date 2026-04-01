<?php declare(strict_types=1); ?>
<div class="section-block">
    <div class="section-block__header">
        <div>
            <span class="results-header__eyebrow">Seller catalog management</span>
            <h2><?= e((string) ($pagination['total_items'] ?? count($products))) ?> products</h2>
        </div>
        <a class="btn btn-brand" href="/admin/product-edit.php">Create product</a>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-success" role="alert"><?= e((string) $notice) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert"><?= e((string) $error) ?></div>
    <?php endif; ?>

    <?php if ($products === []): ?>
        <div class="empty-state">
            <h3>No products found.</h3>
            <p>Create a product to start stocking the marketplace catalog.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Seller</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?= e((string) $product['image_url']) ?>" alt="" style="width:52px;height:52px;border-radius:16px;object-fit:cover;border:1px solid rgba(18,85,168,0.12);">
                                    <div>
                                        <strong><?= e((string) $product['name']) ?></strong><br>
                                        <small class="text-muted"><?= e((string) $product['sku']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= e((string) $product['seller_name']) ?></td>
                            <td><?= e((string) $product['category_name']) ?></td>
                            <td><?= e(money((float) $product['price'])) ?></td>
                            <td><?= e((string) $product['stock_quantity']) ?></td>
                            <td>
                                <?php if ($product['is_active']): ?>
                                    <span class="pill-badge pill-badge--soft" style="color:#245c61;">Active</span>
                                <?php else: ?>
                                    <span class="pill-badge pill-badge--dark">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a class="btn btn-brand-outline" href="/admin/product-edit.php?id=<?= e((string) $product['id']) ?>" style="padding:0.4rem 0.8rem;font-size:0.85rem;">Edit</a>
                                    <form method="post" action="/admin/products.php" onsubmit="return confirm('Delete this product?');">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                                        <button class="btn btn-outline-danger" type="submit" style="padding:0.4rem 0.8rem;font-size:0.85rem;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= render('partials/pagination', ['pagination' => $pagination ?? null]) ?>
    <?php endif; ?>
</div>
