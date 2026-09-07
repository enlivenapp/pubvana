<?php
/**
 * @var \Pubvana\Plugins\Forms\Models\FormSubmission[] $submissions
 * @var \Pubvana\Plugins\Forms\Models\Form[] $forms
 * @var string $adminBase
 * @var int $total
 * @var int $page
 * @var int $perPage
 * @var int|null $filterFormId
 */
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <form method="GET" action="<?= $adminBase ?>/submissions" class="d-flex gap-2">
        <select name="form_id" class="form-select" onchange="this.form.submit()">
            <option value="">All forms</option>
            <?php foreach ($forms as $form): ?>
                <option value="<?= (int) $form->id ?>" <?= $filterFormId === (int) $form->id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($form->name) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    <a href="<?= $adminBase ?>" class="btn btn-outline-secondary">Back to Forms</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Form</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">No submissions found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td>#<?= (int) $submission->id ?></td>
                            <td>
                                <?php
                                $label = 'Form #' . (int) $submission->form_id;
                                foreach ($forms as $form) {
                                    if ((int) $form->id === (int) $submission->form_id) {
                                        $label = $form->name;
                                        break;
                                    }
                                }
                                ?>
                                <?= htmlspecialchars($label) ?>
                            </td>
                            <td><span class="badge bg-info-lt"><?= htmlspecialchars($submission->status) ?></span></td>
                            <td><?= htmlspecialchars((string) $submission->submitted_at) ?></td>
                            <td>
                                <a href="<?= $adminBase ?>/submissions/<?= (int) $submission->id ?>" class="btn btn-sm btn-outline-primary">View</a>
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
                    <a class="page-link" href="?page=<?= $i ?><?= $filterFormId ? '&form_id=' . $filterFormId : '' ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
