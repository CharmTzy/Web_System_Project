<?php declare(strict_types=1); ?>
<div class="section-block">
    <div class="section-block__header">
        <div>
            <span class="results-header__eyebrow">Help center management</span>
            <h2><?= e((string) ($pagination['total_items'] ?? count($questions))) ?> questions</h2>
        </div>
        <a class="btn btn-brand" href="/admin/help-question-edit.php">Add question</a>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-success" role="alert"><?= e((string) $notice) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert"><?= e((string) $error) ?></div>
    <?php endif; ?>

    <form class="catalog-sortbar" method="get" action="/admin/help-questions.php" style="margin-bottom:1rem;">
        <input class="form-control" type="search" name="search" value="<?= e((string) ($filters['search'] ?? '')) ?>" placeholder="Search questions or answers" style="max-width:320px;">
        <div class="catalog-sortbar__controls">
            <select class="form-select" name="category_id" onchange="this.form.submit()" style="min-width:180px;">
                <option value="0">All categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e((string) $category['id']) ?>" <?= (int) ($filters['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>>
                        <?= e((string) $category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-brand-outline" type="submit">Filter</button>
        </div>
    </form>

    <?php if ($questions === []): ?>
        <div class="empty-state">
            <h3>No help questions found.</h3>
            <p>Add a question to populate the help center.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>Question</th>
                        <th>Category</th>
                        <th>Hot</th>
                        <th>Sort order</th>
                        <th>Answer preview</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questions as $question): ?>
                        <tr>
                            <td><strong><?= e((string) $question['question']) ?></strong></td>
                            <td><?= e((string) $question['category_name']) ?></td>
                            <td>
                                <?php if ($question['is_hot']): ?>
                                    <span class="pill-badge pill-badge--soft" style="color:#245c61;">Hot question</span>
                                <?php else: ?>
                                    <span class="pill-badge pill-badge--dark">Standard</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e((string) $question['sort_order']) ?></td>
                            <td><?= e(mb_substr((string) $question['answer'], 0, 90)) ?><?= mb_strlen((string) $question['answer']) > 90 ? '...' : '' ?></td>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a class="btn btn-brand-outline" href="/admin/help-question-edit.php?id=<?= e((string) $question['id']) ?>" style="padding:0.4rem 0.8rem;font-size:0.85rem;">Edit</a>
                                    <form method="post" action="/admin/help-questions.php" onsubmit="return confirm('Delete this help question?');">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="question_id" value="<?= e((string) $question['id']) ?>">
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
