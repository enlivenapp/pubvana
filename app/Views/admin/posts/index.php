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

<div class="card shadow mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
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
                    <tr><td colspan="5" class="text-center text-muted py-4">No posts found. <a href="<?= base_url('admin/posts/create') ?>">Create one!</a></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php if (isset($pager)): ?><?= $pager->links('default', 'bootstrap_full') ?><?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
