<?php declare(strict_types=1); ?>
<div class="section-block">
    <div class="section-block__header">
        <div>
            <span class="results-header__eyebrow">Customer address management</span>
            <h2><?= e((string) count($addresses)) ?> addresses</h2>
        </div>
        <a class="btn btn-brand" href="/admin/address-edit.php">Add address</a>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-success" role="alert"><?= e((string) $notice) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert"><?= e((string) $error) ?></div>
    <?php endif; ?>

    <form class="catalog-sortbar" method="get" action="/admin/addresses.php" style="margin-bottom:1rem;">
        <input class="form-control" type="search" name="search" value="<?= e((string) ($filters['search'] ?? '')) ?>" placeholder="Search customer or address" style="max-width:320px;">
        <div class="catalog-sortbar__controls">
            <select class="form-select" name="user_id" onchange="this.form.submit()" style="min-width:220px;">
                <option value="0">All customers</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?= e((string) $customer['id']) ?>" <?= (int) ($filters['user_id'] ?? 0) === (int) $customer['id'] ? 'selected' : '' ?>>
                        <?= e((string) $customer['name']) ?> (<?= e((string) $customer['email']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-brand-outline" type="submit">Filter</button>
        </div>
    </form>

    <?php if ($addresses === []): ?>
        <div class="empty-state">
            <h3>No addresses found.</h3>
            <p>Create or filter addresses to review customer delivery details.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Recipient</th>
                        <th>Address</th>
                        <th>Label</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($addresses as $address): ?>
                        <tr>
                            <td>
                                <strong><?= e((string) $address['user_name']) ?></strong><br>
                                <small class="text-muted"><?= e((string) $address['user_email']) ?></small>
                            </td>
                            <td><?= e((string) $address['recipient']) ?></td>
                            <td>
                                <?= e((string) $address['line_1']) ?><br>
                                <?php if (!empty($address['line_2'])): ?>
                                    <?= e((string) $address['line_2']) ?><br>
                                <?php endif; ?>
                                <small class="text-muted"><?= e((string) $address['city']) ?>, <?= e((string) $address['state']) ?> <?= e((string) $address['postal_code']) ?></small>
                            </td>
                            <td><?= e((string) $address['label']) ?></td>
                            <td><?= e((string) ($address['phone'] ?? '-')) ?></td>
                            <td>
                                <?php if ($address['is_default']): ?>
                                    <span class="pill-badge pill-badge--soft" style="color:#245c61;">Default</span>
                                <?php else: ?>
                                    <span class="pill-badge pill-badge--dark">Secondary</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a class="btn btn-brand-outline" href="/admin/address-edit.php?id=<?= e((string) $address['id']) ?>" style="padding:0.4rem 0.8rem;font-size:0.85rem;">Edit</a>
                                    <form method="post" action="/admin/addresses.php" onsubmit="return confirm('Delete this address?');">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="address_id" value="<?= e((string) $address['id']) ?>">
                                        <button class="btn btn-outline-danger" type="submit" style="padding:0.4rem 0.8rem;font-size:0.85rem;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
