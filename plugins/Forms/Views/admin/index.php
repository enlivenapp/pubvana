<?php
/**
 * @var \Pubvana\Plugins\Forms\Models\Form[] $forms
 * @var string $adminBase
 * @var int $total
 * @var int $page
 * @var int $perPage
 */
?>

<div class="d-flex align-items-center justify-content-end mb-4">
    <a href="<?= $adminBase ?>/create" class="btn btn-primary">
        <i class="ti ti-plus me-1"></i> New Form
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Entries</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($forms)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">No forms found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($forms as $form): ?>
                        <tr>
                            <td>
                                <a href="<?= $adminBase ?>/<?= (int) $form->id ?>/edit"><?= htmlspecialchars($form->name) ?></a>
                            </td>
                            <td><code><?= htmlspecialchars($form->slug) ?></code></td>
                            <td>
                                <span class="badge bg-<?= $form->status === 'published' ? 'success-lt' : 'secondary-lt' ?>">
                                    <?= htmlspecialchars($form->status) ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= $adminBase ?>/<?= (int) $form->id ?>/submissions" class="link-secondary">View submissions</a>
                            </td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <a href="<?= $adminBase ?>/<?= (int) $form->id ?>/submissions" class="btn btn-sm btn-outline-secondary">Submissions</a>
                                    <a href="<?= $adminBase ?>/<?= (int) $form->id ?>/edit" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="POST" action="<?= $adminBase ?>/<?= (int) $form->id ?>/delete" class="d-inline" onsubmit="return confirm('Delete this form?')">
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
