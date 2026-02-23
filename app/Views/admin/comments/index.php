<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<h1 class="h3 mb-4 text-gray-800">Comments</h1>

<ul class="nav nav-tabs mb-3">
    <?php foreach (['pending', 'approved', 'spam', 'trash'] as $s): ?>
    <li class="nav-item">
        <a class="nav-link <?= $filter === $s ? 'active' : '' ?>" href="<?= base_url('admin/comments?status=' . $s) ?>"><?= ucfirst($s) ?></a>
    </li>
    <?php endforeach; ?>
</ul>

<div class="card shadow mb-4">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="bg-light"><tr><th>Author</th><th>Content</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($comments as $c): ?>
                <tr>
                    <td>
                        <strong><?= esc($c->author_name) ?></strong><br>
                        <small class="text-muted"><?= esc($c->author_email) ?></small>
                    </td>
                    <td><?= esc(excerpt($c->content, 100)) ?></td>
                    <td class="text-muted small"><?= date('M j, Y', strtotime($c->created_at)) ?></td>
                    <td>
                        <?php if ($c->status !== 'approved'): ?>
                        <form method="POST" action="<?= base_url('admin/comments/' . $c->id . '/approve') ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-xs btn-success">Approve</button></form>
                        <?php endif; ?>
                        <?php if ($c->status !== 'spam'): ?>
                        <form method="POST" action="<?= base_url('admin/comments/' . $c->id . '/spam') ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-xs btn-warning">Spam</button></form>
                        <?php endif; ?>
                        <form method="POST" action="<?= base_url('admin/comments/' . $c->id . '/delete') ?>" class="d-inline" onsubmit="return confirm('Delete permanently?')"><?= csrf_field() ?><button class="btn btn-xs btn-danger">Delete</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($comments)): ?><tr><td colspan="4" class="text-center text-muted py-4">No <?= esc($filter) ?> comments.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php if (isset($pager)): ?><?= $pager->links() ?><?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
