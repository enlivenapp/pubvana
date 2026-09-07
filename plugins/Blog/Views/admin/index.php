<?php
/**
 * Blog post listing - admin page.
 *
 * @var string                       $pageTitle
 * @var string                       $adminBase
 * @var \Pubvana\Plugins\Blog\Models\Post[] $posts
 * @var int                          $total
 * @var int                          $page
 * @var int                          $perPage
 * @var string|null                  $status
 */
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="btn-list">
        <a href="<?= $adminBase ?>" class="btn btn-sm <?= $status === null ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
        <a href="<?= $adminBase ?>?status=published" class="btn btn-sm <?= $status === 'published' ? 'btn-primary' : 'btn-outline-secondary' ?>">Published</a>
        <a href="<?= $adminBase ?>?status=draft" class="btn btn-sm <?= $status === 'draft' ? 'btn-primary' : 'btn-outline-secondary' ?>">Draft</a>
        <a href="<?= $adminBase ?>?status=scheduled" class="btn btn-sm <?= $status === 'scheduled' ? 'btn-primary' : 'btn-outline-secondary' ?>">Scheduled</a>
    </div>
    <a href="<?= $adminBase ?>/create" class="btn btn-primary">
        <i class="ti ti-plus me-1"></i> New Post
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Published</th>
                    <th>Views</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posts)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">No posts found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($posts as $p): ?>
                        <tr>
                            <td>
                                <a href="<?= $adminBase ?>/<?= (int) $p->id ?>/edit">
                                    <?= htmlspecialchars($p->title) ?>
                                </a>
                                <?php if ((int) $p->is_featured): ?>
                                    <i class="ti ti-star-filled text-warning ms-1" title="Featured"></i>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $badgeClass = match ($p->status) {
                                    'published' => 'bg-success-lt',
                                    'scheduled' => 'bg-warning-lt',
                                    default     => 'bg-secondary-lt',
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= htmlspecialchars($p->status) ?>
                                </span>
                            </td>
                            <td><?= $p->published_at ? htmlspecialchars($p->published_at) : '-' ?></td>
                            <td><?= (int) $p->views ?></td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <a href="<?= $adminBase ?>/<?= (int) $p->id ?>/edit" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <a href="<?= $adminBase ?>/<?= (int) $p->id ?>/revisions" class="btn btn-sm btn-outline-secondary">Revisions</a>
                                    <form method="POST" action="<?= $adminBase ?>/<?= (int) $p->id ?>/delete"
                                          class="d-inline" onsubmit="return confirm('Delete this post?')">
                                        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $totalPages = (int) ceil($total / $perPage); ?>
<?php if ($totalPages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= $adminBase ?>?page=<?= $i ?><?= $status ? '&status=' . $status : '' ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
