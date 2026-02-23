<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Revisions: <?= esc($post->title) ?></h1>
    <div>
        <a href="<?= base_url('admin/posts/' . $post->id . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Back to Edit</a>
        <a href="<?= base_url('admin/posts') ?>" class="btn btn-sm btn-outline-secondary ml-1">All Posts</a>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<?php if (empty($revisions)): ?>
    <div class="alert alert-info">No revisions saved yet. Revisions are recorded each time you update a post.</div>
<?php else: ?>
<div class="card shadow mb-4">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Author</th>
                    <th>Status</th>
                    <th>Title</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($revisions as $i => $rev): ?>
                <tr>
                    <td class="text-muted small"><?= count($revisions) - $i ?></td>
                    <td><?= esc($rev->created_at) ?></td>
                    <td><?= esc($rev->author_name ?? '—') ?></td>
                    <td><span class="badge badge-<?= $rev->status === 'published' ? 'success' : 'secondary' ?>"><?= esc($rev->status) ?></span></td>
                    <td><?= esc($rev->title) ?></td>
                    <td class="text-right">
                        <a href="<?= base_url('admin/posts/revisions/' . $rev->id) ?>" class="btn btn-xs btn-outline-primary">View</a>
                        <form method="POST" action="<?= base_url('admin/posts/revisions/' . $rev->id . '/restore') ?>" class="d-inline"
                              onsubmit="return confirm('Restore this revision? The current post will be overwritten.')">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-xs btn-outline-warning">Restore</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
