<?php
/**
 * Redirects listing.
 *
 * @var string $pageTitle
 * @var \Pubvana\Plugins\Redirects\Models\Redirect[] $redirects
 * @var string $adminBase
 */
?>

<div class="d-flex align-items-center justify-content-end mb-4">
    <a href="<?= $adminBase ?>/create" class="btn btn-primary">
        <i class="ti ti-plus me-1"></i> New Redirect
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>From</th>
                    <th>To</th>
                    <th>Status</th>
                    <th>Enabled</th>
                    <th>Hits</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($redirects)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">No redirects found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($redirects as $redirect): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($redirect->source_path) ?></code></td>
                            <td class="text-break">
                                <a href="<?= $adminBase ?>/<?= (int) $redirect->id ?>/edit">
                                    <?= htmlspecialchars($redirect->target_url) ?>
                                </a>
                                <?php if (!empty($redirect->notes)): ?>
                                    <div class="small text-secondary mt-1"><?= nl2br(htmlspecialchars((string) $redirect->notes)) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= (int) $redirect->status_code === 301 ? 'blue-lt' : 'yellow-lt' ?>">
                                    <?= (int) $redirect->status_code ?>
                                </span>
                            </td>
                            <td>
                                <?php if ((int) $redirect->enabled === 1): ?>
                                    <span class="badge bg-success-lt">Enabled</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-lt">Disabled</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int) $redirect->hit_count ?></td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <a href="<?= $adminBase ?>/<?= (int) $redirect->id ?>/edit" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="POST" action="<?= $adminBase ?>/<?= (int) $redirect->id ?>/delete"
                                          class="d-inline" onsubmit="return confirm('Delete this redirect?')">
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
