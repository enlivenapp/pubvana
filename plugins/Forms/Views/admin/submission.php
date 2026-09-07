<?php
/**
 * @var \Pubvana\Plugins\Forms\Models\FormSubmission $submission
 * @var \Pubvana\Plugins\Forms\Models\Form|null $form
 * @var string $adminBase
 * @var array $payload
 */
?>

<div class="d-flex align-items-center justify-content-end mb-4">
    <div class="btn-list">
        <?php if ($form !== null): ?>
            <a href="<?= $adminBase ?>/<?= (int) $form->id ?>/submissions" class="btn btn-outline-secondary">Back to Form Submissions</a>
        <?php endif; ?>
        <a href="<?= $adminBase ?>/submissions" class="btn btn-outline-secondary">All Submissions</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Submission Metadata</h3>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Submission ID</dt>
            <dd class="col-sm-9">#<?= (int) $submission->id ?></dd>

            <dt class="col-sm-3">Form</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($form?->name ?? ('Form #' . (int) $submission->form_id)) ?></dd>

            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($submission->status) ?></dd>

            <dt class="col-sm-3">Submitted</dt>
            <dd class="col-sm-9"><?= htmlspecialchars((string) $submission->submitted_at) ?></dd>

            <dt class="col-sm-3">IP Address</dt>
            <dd class="col-sm-9"><?= htmlspecialchars((string) ($submission->ip_address ?? '')) ?></dd>

            <dt class="col-sm-3">Referrer</dt>
            <dd class="col-sm-9"><?= htmlspecialchars((string) ($submission->referrer_url ?? '')) ?></dd>
        </dl>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Payload</h3>
    </div>
    <div class="card-body">
        <?php if (empty($payload)): ?>
            <p class="text-secondary mb-0">No payload data stored.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payload as $key => $value): ?>
                            <tr>
                                <td><code><?= htmlspecialchars((string) $key) ?></code></td>
                                <td><?= nl2br(htmlspecialchars(is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_SLASHES))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
