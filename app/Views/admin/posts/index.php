<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Posts</h1>
    <a href="<?= base_url('admin/posts/create') ?>" class="btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-plus fa-sm"></i> New Post
    </a>
</div>

<!-- Filter tabs -->
<ul class="nav nav-tabs mb-3">
    <?php foreach (['', 'draft', 'published', 'scheduled'] as $s): ?>
    <li class="nav-item">
        <a class="nav-link <?= $filter === $s ? 'active' : '' ?>" href="<?= base_url('admin/posts' . ($s ? '?status=' . $s : '')) ?>">
            <?= $s ? ucfirst($s) : 'All' ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<form id="bulk-form" method="POST" action="<?= base_url('admin/posts/bulk') ?>">
<?= csrf_field() ?>

<!-- Bulk action toolbar (hidden until ≥1 checkbox checked) -->
<div id="bulk-toolbar" class="mb-3 d-none">
    <div class="d-flex align-items-center gap-2">
        <span id="bulk-count" class="text-muted small me-2"></span>
        <select name="action" class="form-control form-control-sm" style="width:auto">
            <option value="">— Select action —</option>
            <option value="publish">Publish</option>
            <option value="unpublish">Unpublish (set to Draft)</option>
            <option value="delete">Delete</option>
        </select>
        <button type="submit" class="btn btn-sm btn-warning ml-2"
                onclick="return confirm('Apply bulk action to selected posts?')">
            Apply
        </button>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th style="width:40px">
                            <input type="checkbox" id="select-all" title="Select all">
                        </th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Views</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($posts as $post): ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="ids[]" value="<?= $post->id ?>" class="post-checkbox">
                        </td>
                        <td>
                            <a href="<?= base_url('admin/posts/' . $post->id . '/edit') ?>"><?= esc($post->title) ?></a>
                        </td>
                        <td>
                            <span class="badge badge-<?= $post->status === 'published' ? 'success' : ($post->status === 'draft' ? 'secondary' : 'warning') ?>">
                                <?= esc($post->status) ?>
                            </span>
                        </td>
                        <td><?= number_format($post->views) ?></td>
                        <td class="text-muted small"><?= date('M j, Y', strtotime($post->created_at)) ?></td>
                        <td>
                            <a href="<?= base_url('admin/posts/' . $post->id . '/edit') ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="<?= post_url($post->slug) ?>" class="btn btn-sm btn-outline-secondary" target="_blank">View</a>
                            <form method="POST" action="<?= base_url('admin/posts/' . $post->id . '/delete') ?>" class="d-inline" onsubmit="return confirm('Delete this post?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($posts)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No posts found. <a href="<?= base_url('admin/posts/create') ?>">Create one!</a></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</form>

<?php if (isset($pager)): ?><?= $pager->links('default', 'bootstrap_full') ?><?php endif; ?>

<script>
(function () {
    var selectAll  = document.getElementById('select-all');
    var toolbar    = document.getElementById('bulk-toolbar');
    var countEl    = document.getElementById('bulk-count');
    var checkboxes = document.querySelectorAll('.post-checkbox');

    function updateToolbar() {
        var checked = document.querySelectorAll('.post-checkbox:checked').length;
        if (checked > 0) {
            toolbar.classList.remove('d-none');
            countEl.textContent = checked + ' post(s) selected';
        } else {
            toolbar.classList.add('d-none');
            countEl.textContent = '';
        }
        selectAll.indeterminate = checked > 0 && checked < checkboxes.length;
        selectAll.checked = checked === checkboxes.length && checkboxes.length > 0;
    }

    selectAll.addEventListener('change', function () {
        checkboxes.forEach(function (cb) { cb.checked = selectAll.checked; });
        updateToolbar();
    });

    checkboxes.forEach(function (cb) {
        cb.addEventListener('change', updateToolbar);
    });
})();
</script>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
