<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<h1 class="h3 mb-4 text-gray-800">Categories</h1>

<div class="row">
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Add Category</h6></div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('admin/categories/create') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group"><label>Name *</label><input type="text" name="name" class="form-control" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                    <button type="submit" class="btn btn-primary btn-block">Add Category</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="bg-light"><tr><th>Name</th><th>Slug</th><th>Posts</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?= esc($cat->name) ?></td>
                            <td><code><?= esc($cat->slug) ?></code></td>
                            <td><?= $cat->post_count ?></td>
                            <td>
                                <a href="<?= base_url('admin/categories/' . $cat->id . '/edit') ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="<?= base_url('admin/categories/' . $cat->id . '/delete') ?>" class="d-inline" onsubmit="return confirm('Delete?')">
                                    <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($categories)): ?><tr><td colspan="4" class="text-center text-muted py-4">No categories yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
