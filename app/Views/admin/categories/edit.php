<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Edit Category</h1>
    <a href="<?= base_url('admin/categories') ?>" class="btn btn-sm btn-outline-secondary">Back</a>
</div>
<div class="card shadow mb-4" style="max-width:600px">
    <div class="card-body">
        <form method="POST" action="<?= base_url('admin/categories/' . $category->id . '/edit') ?>">
            <?= csrf_field() ?>
            <div class="form-group"><label>Name *</label><input type="text" name="name" class="form-control" required value="<?= esc($category->name) ?>"></div>
            <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3"><?= esc($category->description) ?></textarea></div>
            <button type="submit" class="btn btn-primary">Update Category</button>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
