<?php
/**
 * Category create/edit - admin form.
 *
 * @var string                                      $pageTitle
 * @var string                                      $adminBase
 * @var \Pubvana\Plugins\Blog\Models\Category|null  $category
 * @var \Pubvana\Plugins\Blog\Models\Category[]     $categories
 */

$isEdit = $category !== null;
$action = $isEdit
    ? $adminBase . '/categories/' . (int) $category->id . '/update'
    : $adminBase . '/categories/store';
?>

<div class="d-flex align-items-center justify-content-end mb-4">
    <a href="<?= $adminBase ?>/categories" class="btn btn-outline-secondary">Back</a>
</div>

<form method="POST" action="<?= $action ?>">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label" for="name">Name</label>
                <input type="text" name="name" id="name" class="form-control"
                       value="<?= htmlspecialchars($isEdit ? $category->name : '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="slug">Slug</label>
                <input type="text" name="slug" id="slug" class="form-control"
                       value="<?= htmlspecialchars($isEdit ? $category->slug : '') ?>"
                       placeholder="Auto-generated from name">
            </div>
            <div class="mb-3">
                <label class="form-label" for="description">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3"><?= htmlspecialchars($isEdit ? ($category->description ?? '') : '') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label" for="parent_id">Parent Category</label>
                <select name="parent_id" id="parent_id" class="form-select">
                    <option value="">None</option>
                    <?php foreach ($categories as $cat): ?>
                        <?php if ($isEdit && (int) $cat->id === (int) $category->id) continue; ?>
                        <option value="<?= (int) $cat->id ?>"
                                <?= ($isEdit && (int) ($category->parent_id ?? 0) === (int) $cat->id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update' : 'Create' ?> Category</button>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var nameEl = document.getElementById('name');
    var slugEl = document.getElementById('slug');
    var slugEdited = slugEl.value !== '';

    slugEl.addEventListener('input', function () {
        slugEdited = this.value !== '';
    });

    nameEl.addEventListener('input', function () {
        if (slugEdited) return;
        slugEl.value = this.value.toLowerCase().trim()
            .replace(/&/g, 'and')
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/[\s]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    });
});
</script>
