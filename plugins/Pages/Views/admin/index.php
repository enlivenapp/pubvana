<?php
/**
 * Page listing - admin page.
 *
 * @var string $pageTitle
 * @var \Pubvana\Plugins\Pages\Models\Page[] $pages
 * @var int $total
 * @var int $page
 * @var int $perPage
 * @var string $adminBase
 * @var string $publicBase
 */
?>

<div class="d-flex align-items-center justify-content-end mb-4">
    <a href="<?= $adminBase ?>/create" class="btn btn-primary">
        <i class="ti ti-plus me-1"></i> New Page
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Author</th>
                    <th>Last Updated</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pages)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">No pages found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pages as $p): ?>
                        <tr>
                            <td>
                                <a href="<?= $adminBase ?>/<?= (int) $p->id ?>/edit">
                                    <?= htmlspecialchars($p->title ?? 'Untitled') ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($p->status === 'published'): ?>
                                    <span class="badge bg-success-lt">Published</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-lt">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-secondary">
                                <?= (int) $p->created_by ?>
                            </td>
                            <td class="text-secondary">
                                <?= htmlspecialchars($p->updated_at ?? 'Never') ?>
                            </td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <a href="<?= $adminBase ?>/<?= (int) $p->id ?>/edit" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <a href="<?= $adminBase ?>/<?= (int) $p->id ?>/revisions" class="btn btn-sm btn-outline-secondary">Revisions</a>
                                    <?php if ($p->status === 'published'): ?>
                                    <a href="<?= $publicBase ?>/<?= htmlspecialchars($p->slug) ?>" class="btn btn-sm btn-outline-success" target="_blank">View</a>
                                    <?php endif; ?>
                                    <form method="POST" action="<?= $adminBase ?>/<?= (int) $p->id ?>/delete"
                                          class="d-inline" onsubmit="return confirm('Delete this page?')">
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
                    <a class="page-link" href="<?= $adminBase ?>?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
