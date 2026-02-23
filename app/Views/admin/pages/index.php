<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Pages</h1>
    <a href="<?= base_url('admin/pages/create') ?>" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm"></i> New Page</a>
</div>

<div class="card shadow mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr><th>Title</th><th>Slug</th><th>Status</th><th>System</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($pages as $page): ?>
                <tr>
                    <td><a href="<?= base_url('admin/pages/' . $page->id . '/edit') ?>"><?= esc($page->title) ?></a></td>
                    <td><code><?= esc($page->slug) ?></code></td>
                    <td><span class="badge badge-<?= $page->status === 'published' ? 'success' : 'secondary' ?>"><?= esc($page->status) ?></span></td>
                    <td><?= $page->is_system ? '<span class="badge badge-info">System</span>' : '—' ?></td>
                    <td>
                        <a href="<?= base_url('admin/pages/' . $page->id . '/edit') ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                        <?php if (! $page->is_system): ?>
                        <form method="POST" action="<?= base_url('admin/pages/' . $page->id . '/delete') ?>" class="d-inline" onsubmit="return confirm('Delete?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($pages)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No pages yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
