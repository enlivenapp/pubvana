<?php
/**
 * Comment settings + host manager - admin page.
 *
 * @var string        $pageTitle
 * @var bool          $enabled
 * @var bool          $guestComments
 * @var string        $defaultStatus
 * @var int           $maxNestingDepth
 * @var array         $hosts      Comment hosts keyed by contributor key, each with 'label', 'description', 'enabled'
 * @var array<string, array{comments:int, pending:int}> $hostCounts
 * @var string        $adminBase
 */
?>

<form method="POST" action="<?= $adminBase ?>/settings">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Comment Settings</h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="comments_enabled" value="1"
                               <?= $enabled ? 'checked' : '' ?>>
                        <span class="form-check-label">Enable comments</span>
                    </label>
                    <div class="text-secondary small">Master toggle for the entire comment system.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="allow_guest_comments" value="1"
                               <?= $guestComments ? 'checked' : '' ?>>
                        <span class="form-check-label">Allow guest comments</span>
                    </label>
                    <div class="text-secondary small">Allow non-logged-in users to post comments.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="default_status">Default status</label>
                    <select class="form-select" id="default_status" name="default_status">
                        <option value="pending" <?= $defaultStatus === 'pending' ? 'selected' : '' ?>>Pending (require moderation)</option>
                        <option value="approved" <?= $defaultStatus === 'approved' ? 'selected' : '' ?>>Approved (auto-publish)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="max_nesting_depth">Max nesting depth</label>
                    <input type="number" class="form-control" id="max_nesting_depth" name="max_nesting_depth"
                           min="1" value="<?= (int) $maxNestingDepth ?>">
                </div>
            </div>
            <div class="text-secondary small mt-3">
                Captcha for the comment form is configured site-wide under
                <a href="/admin/captcha">Settings &gt; Captcha</a>.
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Comment Hosts</h3>
                <div class="text-secondary small">
                    Content plugins that can host comments. Enable a host to accept (and display)
                    comments on its content. Hosts are off until you enable them.
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th class="w-1">Enabled</th>
                        <th>Host</th>
                        <th>Description</th>
                        <th class="w-1">Total</th>
                        <th class="w-1">Pending</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($hosts)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">
                                No comment hosts are registered. Content plugins register themselves as
                                comment hosts (e.g. Blog, Pages).
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($hosts as $key => $host): ?>
                            <?php $counts = $hostCounts[$key] ?? ['comments' => 0, 'pending' => 0]; ?>
                            <tr>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox"
                                               id="host_<?= htmlspecialchars($key) ?>"
                                               name="host[<?= htmlspecialchars($key) ?>]"
                                               value="1"
                                               <?= $host['enabled'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="host_<?= htmlspecialchars($key) ?>">
                                            <span class="visually-hidden">Enable</span>
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-medium"><?= htmlspecialchars((string) ($host['label'] ?? $key)) ?></div>
                                    <div class="text-secondary small"><code><?= htmlspecialchars($key) ?></code></div>
                                </td>
                                <td class="text-secondary">
                                    <?= htmlspecialchars((string) ($host['description'] ?? '')) ?>
                                </td>
                                <td class="text-secondary"><?= (int) $counts['comments'] ?></td>
                                <td>
                                    <?php if ($counts['pending'] > 0): ?>
                                        <span class="badge bg-warning-lt"><?= (int) $counts['pending'] ?></span>
                                    <?php else: ?>
                                        <span class="text-secondary">0</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i> Save Comment Settings
        </button>
    </div>
</form>
