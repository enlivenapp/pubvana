<?php

/**
 * Updates admin screen, laid out after the v2 Updates page: settings card
 * (auto-update toggle + crontab lines), status banners, preflight table
 * with required/optional badges, confirmation modal, granular progress
 * polling, and the skip list.
 *
 * @var array<string, mixed> $state          Check state from UpdateService
 * @var bool                 $auto           Auto-update setting
 * @var list<string>         $skipped        Skipped versions
 * @var list<array{name: string, ok: bool, detail: string, hard: bool}> $preflight
 * @var array{themes: list<array<string, mixed>>, blocks: list<array{name: string, updates_with: string}>, plugins: list<array<string, mixed>>} $addons
 * @var array<string, mixed>|null $progress  Live progress payload (null when none)
 * @var bool                 $is_locked      Whether an operation is running
 * @var string               $changelog_url  Human changelog link
 * @var string               $adminBase      Full admin URL base for this plugin
 *
 * @package  Pubvana\Plugins\Updates
 * @copyright 2026 enlivenapp
 * @license  MIT
 */

$status    = (string) ($state['status'] ?? 'unknown');
$current   = (string) ($state['current_version'] ?? '');
$target    = (string) ($state['target_version'] ?? '');
$latest    = (string) ($state['latest_version'] ?? '');
$cappedBy  = $state['capped_by'] ?? null;
$constraints = (array) ($state['constraints'] ?? []);
$breaking  = (array) ($state['breaking_changes'] ?? []);
$notes     = (array) ($state['migration_notes'] ?? []);
$notices   = (array) ($state['notices'] ?? []);
$checkedAt = (string) ($state['checked_at'] ?? '');
$checked   = $checkedAt !== '' ? date('M j, Y g:ia', strtotime($checkedAt) ?: time()) : 'never';

$hasUpdate   = $status === 'available' && $target !== '';
$hasBreaking = $breaking !== [];
$running     = is_array($progress) && (($progress['status'] ?? '') === 'in_progress');

$allHardPass = true;
foreach ($preflight as $preflightCheck) {
    if ($preflightCheck['hard'] && !$preflightCheck['ok']) {
        $allHardPass = false;
        break;
    }
}

$phpBinary = PHP_BINARY ?: (PHP_BINDIR . '/php');

/** Renders the compatibility table for plugins/themes that cap the latest release. */
$renderConstraints = static function () use ($constraints, $latest): void {
    ?>
    <div class="alert alert-secondary" role="alert">
        <div class="d-flex">
            <div><i class="ti ti-info-circle icon alert-icon"></i></div>
            <div class="flex-grow-1">
                <strong>Version <?= htmlspecialchars($latest) ?> is also available.</strong>
                It requires the following plugins or themes to be updated or removed first:
                <table class="table table-sm mb-2 mt-2">
                    <thead>
                        <tr><th>Name</th><th>Compatibility</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($constraints as $constraint): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $constraint['name']) ?></td>
                            <td>
                                <?php if ($constraint['min'] !== null): ?>
                                    Requires Pubvana <?= htmlspecialchars((string) $constraint['min']) ?>+
                                <?php endif; ?>
                                <?= $constraint['min'] !== null && $constraint['max'] !== null ? ' / ' : '' ?>
                                <?php if ($constraint['max'] !== null): ?>
                                    Supports up to <?= htmlspecialchars((string) $constraint['max']) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="small text-muted mb-0">
                    You can remove incompatible plugins or themes if issues occur.
                    A backup is created before every update.
                </p>
            </div>
        </div>
    </div>
    <?php
};
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="h4 mb-0">Updates</h2>
    <form method="POST" action="<?= $adminBase ?>/check" class="d-inline">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
        <button type="submit" class="btn btn-outline-secondary btn-sm" <?= $running ? 'disabled' : '' ?>>
            <i class="ti ti-refresh me-1"></i> Check for updates
        </button>
    </form>
</div>

<!-- Update Settings -->
<div class="card mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold"><i class="ti ti-settings me-1"></i> Update Settings</h6>
    </div>
    <div class="card-body">
        <div class="row align-items-start">
            <div class="col-md-4 mb-3 mb-md-0">
                <label class="d-block fw-bold small mb-1">Pubvana Auto-Update</label>
                <small class="form-text text-muted d-block">
                    When <b>Automatic</b>, Pubvana updates are applied automatically if there are no breaking changes.  Breaking changes require your approval. If Automatic and you visit this page it will trigger and automatic update if one is available.
                </small>
                <div class="btn-group" role="group" aria-label="Pubvana Auto-Update">
                    <input type="radio" class="btn-check" name="auto_update" id="auto-update-manual"
                           value="0" autocomplete="off" <?= !$auto ? 'checked' : '' ?>>
                    <label class="btn btn-outline-secondary" for="auto-update-manual">Manual</label>
                    <input type="radio" class="btn-check" name="auto_update" id="auto-update-automatic"
                           value="1" autocomplete="off" <?= $auto ? 'checked' : '' ?>>
                    <label class="btn btn-outline-secondary" for="auto-update-automatic">Automatic</label>
                </div>
                
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                
            </div>
            <div class="col-md-4">
                <label class="d-block fw-bold small mb-1">Cron</label>
                <code class="d-block bg-secondary-lt p-2 rounded small user-select-all mb-1">* * * * *    <?= htmlspecialchars($phpBinary) ?> <?= htmlspecialchars(PROJECT_ROOT) ?>/cron 1m</code>
                <code class="d-block bg-secondary-lt p-2 rounded small user-select-all mb-1">7 */4 * * *  <?= htmlspecialchars($phpBinary) ?> <?= htmlspecialchars(PROJECT_ROOT) ?>/cron 4h</code>
                <code class="d-block bg-secondary-lt p-2 rounded small user-select-all">15 3 * * *   <?= htmlspecialchars($phpBinary) ?> <?= htmlspecialchars(PROJECT_ROOT) ?>/cron 24h</code>
                <small class="form-text text-muted d-block">
                    Add these lines to your server's crontab to run Pubvana's cron tasks. <b>Highly Recommended</b>
                </small>
            </div>
        </div>
        <div class="mt-3">
            <button type="button" class="btn btn-primary btn-sm" id="save-update-settings-btn">
                <i class="ti ti-device-floppy me-1"></i> Save
            </button>
            <span class="ms-2 small text-success" id="settings-saved-msg" style="display:none;">
                <i class="ti ti-circle-check"></i> Update settings saved.
            </span>
        </div>
    </div>
</div>

<?php if ($status === 'error' && !empty($state['error'])): ?>
<div class="alert alert-warning" role="alert">
    <div class="d-flex">
        <div><i class="ti ti-alert-triangle icon alert-icon"></i></div>
        <div><strong>Check failed:</strong> <?= htmlspecialchars((string) $state['error']) ?></div>
    </div>
</div>
<?php endif; ?>

<!-- Granular progress -->
<div id="update-progress" class="card mb-4" style="<?= $running ? '' : 'display:none;' ?>">
    <div class="card-body">
        <h6 class="fw-bold" id="update-progress-label">Working...</h6>
        <div class="progress mb-3">
            <div class="progress-bar progress-bar-striped progress-bar-animated" id="update-progress-bar"
                 role="progressbar" style="width: 0%"></div>
        </div>
        <small class="text-muted d-block mb-3" id="update-progress-detail"></small>
        <ul class="list-group list-group-flush" id="update-progress-phases"></ul>
    </div>
</div>

<?php if ($hasUpdate): ?>
<div class="alert alert-info" role="alert">
    <div class="d-flex">
        <div><i class="ti ti-circle-arrow-up icon alert-icon"></i></div>
        <div>
            <strong>Version <?= htmlspecialchars($target) ?> is available.</strong>
            Currently running <?= htmlspecialchars($current) ?>.
            <small class="text-muted d-block">Last checked: <?= htmlspecialchars($checked) ?></small>
        </div>
    </div>
</div>

<?php if ($cappedBy !== null): ?>
<?= $renderConstraints() ?>
<?php endif; ?>

<?php if ($hasBreaking): ?>
<div class="alert alert-danger" role="alert">
    <h6 class="fw-bold"><i class="ti ti-alert-triangle me-1"></i> Breaking changes</h6>
    <ul class="mb-0">
        <?php foreach ($breaking as $line): ?>
        <li><?= htmlspecialchars((string) $line) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if ($notices !== []): ?>
<div class="alert alert-info" role="alert">
    <h6 class="fw-bold"><i class="ti ti-info-circle me-1"></i> Notices</h6>
    <ul class="mb-0">
        <?php foreach ($notices as $line): ?>
        <li><?= htmlspecialchars((string) $line) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if ($notes !== []): ?>
<div class="alert alert-warning" role="alert">
    <h6 class="fw-bold"><i class="ti ti-database me-1"></i> Migration notes</h6>
    <ul class="mb-0">
        <?php foreach ($notes as $line): ?>
        <li><?= htmlspecialchars((string) $line) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if ($preflight !== []): ?>
<div class="card mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold">Preflight checks</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-vcenter mb-0">
            <tbody>
            <?php foreach ($preflight as $preflightCheck): ?>
                <tr>
                    <td class="w-1 ps-3">
                        <i class="ti <?= $preflightCheck['ok']
                            ? 'ti-circle-check text-success'
                            : ($preflightCheck['hard'] ? 'ti-circle-x text-danger' : 'ti-alert-triangle text-warning') ?>"></i>
                    </td>
                    <td><?= htmlspecialchars($preflightCheck['name']) ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($preflightCheck['detail']) ?></td>
                    <td class="text-end pe-3 w-1">
                        <span class="badge bg-<?= $preflightCheck['hard'] ? 'danger' : 'secondary' ?>-lt">
                            <?= $preflightCheck['hard'] ? 'Required' : 'Optional' ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<p class="text-muted small">
    A full backup is taken before anything changes. If an update fails, restore the
    pre-update snapshot from <a href="/admin/backups">Tools &gt; Backups</a>.
    <a href="<?= htmlspecialchars($changelog_url) ?>" target="_blank" rel="noopener">Full changelog</a>.
</p>

<div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
    <button type="button" class="btn btn-primary btn-lg" id="apply-btn"
            <?= (!$allHardPass || $is_locked) ? 'disabled' : '' ?>>
        <i class="ti ti-rocket me-1"></i> Update to version <?= htmlspecialchars($target) ?>
    </button>
    <span class="d-inline-flex align-items-center gap-1" title="Standing of this release in the Pubvana trust service cache">
        <?= trust_badge($trust['status'] ?? 'none', $trust['warning'] ?? null) ?>
    </span>
</div>
<?php if (($trust['status'] ?? 'none') === 'unknown'): ?>
<p class="text-warning small mb-2">
    This release hasn't been evaluated by the Pubvana trust service yet. Applying it will ask for a confirmation.
</p>
<?php endif; ?>
<?php if (!$allHardPass): ?>
<p class="text-danger small">Preflight checks failed.</p>
<?php endif; ?>

<form method="POST" action="<?= $adminBase ?>/skip" class="d-inline ms-2">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="version" value="<?= htmlspecialchars($target) ?>">
    <button type="submit" class="btn btn-outline-secondary" <?= $is_locked ? 'disabled' : '' ?>>
        Skip this version
    </button>
</form>

<?php elseif ($status === 'up_to_date'): ?>
<div class="alert alert-success" role="alert">
    <div class="d-flex">
        <div><i class="ti ti-circle-check icon alert-icon"></i></div>
        <div>
            Pubvana is up to date. Currently running version <?= htmlspecialchars($current) ?>.
            <small class="text-muted d-block">Last checked: <?= htmlspecialchars($checked) ?></small>
        </div>
    </div>
</div>

<?php if ($cappedBy !== null): ?>
<?= $renderConstraints() ?>
<?php endif; ?>

<?php if ($skipped !== []): ?>
<div class="card mb-4">
    <div class="card-body">
        <strong>Skipped versions</strong>
        <p class="text-muted small mb-2">Skipped releases are never offered. Remove one to offer it again.</p>
        <?php foreach ($skipped as $skippedVersion): ?>
        <form method="POST" action="<?= $adminBase ?>/unskip" class="d-inline me-2 mb-1">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="version" value="<?= htmlspecialchars($skippedVersion) ?>">
            <button type="submit" class="btn btn-sm btn-outline-secondary">
                <?= htmlspecialchars($skippedVersion) ?> <i class="ti ti-x ms-1"></i>
            </button>
        </form>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Addons -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="h4 mb-0">Addons</h2>
    <div>
        <button type="button" class="btn btn-outline-primary btn-sm me-1" disabled
                title="Addon updates arrive when a marketplace source is installed.">
            <i class="ti ti-refresh me-1"></i> Check All
        </button>
        <button type="button" class="btn btn-primary btn-sm" disabled
                title="Addon updates arrive when a marketplace source is installed.">
            <i class="ti ti-download me-1"></i> Update All
        </button>
    </div>
</div>

<?php
$addonSections = [
    'Themes'  => $addons['themes'],
    'Blocks'  => $addons['blocks'],
    'Plugins' => $addons['plugins'],
];
foreach ($addonSections as $addonLabel => $addonRows): ?>
<div class="card mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold"><?= htmlspecialchars($addonLabel) ?></h6>
    </div>
    <div class="table-responsive">
        <?php if ($addonRows === []): ?>
            <p class="text-muted text-center py-3 mb-0">No <?= htmlspecialchars(strtolower($addonLabel)) ?> installed.</p>
        <?php elseif ($addonLabel === 'Blocks'): ?>
        <table class="table table-sm table-vcenter mb-0">
            <thead>
                <tr>
                    <th class="ps-3">Block</th>
                    <th class="text-center">Updates with</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($addonRows as $addonRow): ?>
                <tr>
                    <td class="ps-3"><?= htmlspecialchars($addonRow['name']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($addonRow['updates_with']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <table class="table table-sm table-vcenter mb-0">
            <thead>
                <tr>
                    <th class="ps-3">Name</th>
                    <th>Version</th>
                    <th>Trust</th>
                    <th>Latest</th>
                    <th>Auto-Update</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($addonRows as $addonRow): ?>
                <?php
                $recheckKind = $addonLabel === 'Themes' ? 'theme' : 'plugin';
                $recheckHandle = $recheckKind === 'theme'
                    ? (string) ($addonRow['folder'] ?? '')
                    : (string) ($addonRow['id'] ?? '');
                ?>
                <tr>
                    <td class="ps-3"><?= htmlspecialchars($addonRow['name']) ?></td>
                    <td><?= $addonRow['version'] !== null ? htmlspecialchars($addonRow['version']) : '-' ?></td>
                    <td class="text-nowrap">
                        <span data-trust-cell><?= trust_badge($addonRow['trust_status'] ?? 'none', $addonRow['trust_warning'] ?? null) ?></span>
                        <?php if ($recheckHandle !== ''): ?>
                        <button type="button" class="btn btn-icon btn-sm text-secondary"
                                data-trust-recheck="<?= $recheckKind ?>"
                                data-handle="<?= htmlspecialchars($recheckHandle) ?>"
                                title="Recheck with the Pubvana trust service">
                            <i class="ti ti-refresh"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                    <td>-</td>
                    <td>-</td>
                    <td><span class="text-muted small">No update source</span></td>
                    <td class="text-end pe-3"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- Confirm Update modal (v2 pattern) -->
<div class="modal modal-blur fade" id="confirm-update-modal" tabindex="-1" style="display:none" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="ti ti-rocket me-2"></i>Confirm Update</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>This will backup your site, download the update, and apply it.</p>
                <p class="d-flex align-items-center gap-2 mb-1">
                    Trust standing:
                    <?= trust_badge($trust['status'] ?? 'none', $trust['warning'] ?? null) ?>
                </p>
                <p class="small text-muted mb-0">
                    Your <code>.env</code> and <code>app/config/shield.php</code> are never overwritten.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirm-update-btn">
                    <i class="ti ti-rocket me-1"></i> Update Now
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal-backdrop fade" id="confirm-update-backdrop" style="display:none"></div>

<!-- Trust confirmation modal: applying a release the Pubvana trust service
     has not evaluated. Confirming resubmits the apply request with force_trust=1. -->
<div class="modal modal-blur fade" id="trust-confirm-modal" tabindex="-1" style="display:none" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Apply without an evaluation?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>
                    <strong>Pubvana</strong>
                    <span id="trust-confirm-version" class="text-muted"></span>
                    hasn't been evaluated by the Pubvana trust service.
                </p>
                <p class="small text-muted mb-0">Apply anyway?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="trust-apply-anyway">
                    <i class="ti ti-rocket me-1"></i> Apply Anyway
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal-backdrop fade" id="trust-confirm-backdrop" style="display:none"></div>

<!-- Trust blocked modal: the trust service found the release malicious. No
     proceed path on purpose. -->
<div class="modal modal-blur fade" id="trust-blocked-modal" tabindex="-1" style="display:none" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-alert-triangle text-danger me-2"></i>
                    Update blocked
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">
                    <strong>Pubvana</strong> has been evaluated by the Pubvana trust service and found to be malicious.
                </p>
                <p id="trust-blocked-warning" class="mb-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
<div class="modal-backdrop fade" id="trust-blocked-backdrop" style="display:none"></div>

<script>
(function () {
    var statusUrl = '<?= $adminBase ?>/status';
    var card      = document.getElementById('update-progress');
    var bar       = document.getElementById('update-progress-bar');
    var label     = document.getElementById('update-progress-label');
    var detail    = document.getElementById('update-progress-detail');
    var phaseList = document.getElementById('update-progress-phases');
    var running   = <?= $running ? 'true' : 'false' ?>;
    var timer     = null;

    function esc(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function render(data) {
        var pct = Math.max(0, Math.min(100, parseInt(data.percent || 0, 10)));
        bar.style.width = pct + '%';
        label.textContent = data.phase_label || 'Working...';
        detail.textContent = data.detail || '';
        phaseList.innerHTML = '';

        var phases = data.phases || [];
        phases.forEach(function (phase) {
            var li = document.createElement('li');
            li.className = 'list-group-item px-0 py-1';
            var icon = 'ti-circle text-muted';
            if (phase.status === 'done') { icon = 'ti-circle-check text-success'; }
            if (phase.status === 'active') { icon = 'ti-circle-dot text-primary'; }
            li.innerHTML = '<i class="ti ' + icon + ' me-2"></i>' + esc(phase.label);
            phaseList.appendChild(li);
        });
    }

    function showCard() {
        card.style.display = '';
        var applyButton = document.getElementById('apply-btn');
        if (applyButton) { applyButton.disabled = true; }
    }

    function poll() {
        fetch(statusUrl).then(function (r) { return r.json(); }).then(function (data) {
            if (data.status === 'in_progress') {
                render(data);
                timer = setTimeout(poll, 1000);
            } else if (data.status === 'completed' || data.status === 'error') {
                render(data);
                if (data.status === 'completed') {
                    bar.classList.remove('progress-bar-animated');
                    bar.classList.add('bg-success');
                    bar.style.width = '100%';
                    label.textContent = 'Update complete. Reloading...';
                } else {
                    bar.classList.remove('progress-bar-animated');
                    bar.classList.add('bg-danger');
                    label.textContent = 'Update failed';
                    detail.textContent = data.error || '';
                }
                setTimeout(function () { location.reload(); }, 2500);
            }
        }).catch(function () {
            timer = setTimeout(poll, 2000);
        });
    }

    // ------------------------------------------------------------------
    // Apply: button opens the confirmation modal; confirm starts the run.
    // Gate order is trust first, then breaking changes are already
    // confirmed by the page (confirm_breaking). The trust answer arrives
    // as needsConfirm (show the modal, resubmit with force_trust) or
    // blocked (show the reason, no proceed).
    // ------------------------------------------------------------------
    var applyBtn        = document.getElementById('apply-btn');
    var modal           = document.getElementById('confirm-update-modal');
    var backdrop        = document.getElementById('confirm-update-backdrop');
    var confirmBtn      = document.getElementById('confirm-update-btn');
    var applyStarted    = false;

    function showModal(m, b) {
        m.style.display = 'block';
        m.classList.add('show');
        m.removeAttribute('aria-hidden');
        b.style.display = 'block';
        b.classList.add('show');
    }

    function hideModal(m, b) {
        m.style.display = 'none';
        m.classList.remove('show');
        m.setAttribute('aria-hidden', 'true');
        b.style.display = 'none';
        b.classList.remove('show');
    }

    function openModal() {
        showModal(modal, backdrop);
    }

    function closeModal() {
        hideModal(modal, backdrop);
    }

    var trustModal      = document.getElementById('trust-confirm-modal');
    var trustBackdrop   = document.getElementById('trust-confirm-backdrop');
    var trustAnywayBtn  = document.getElementById('trust-apply-anyway');
    var blockedModal    = document.getElementById('trust-blocked-modal');
    var blockedBackdrop = document.getElementById('trust-blocked-backdrop');

    function startApply(forceTrust) {
        if (applyStarted) { return; }
        applyStarted = true;

        var body = new FormData();
        body.append('_csrf_token', '<?= csrf_token() ?>');
        body.append('confirm_breaking', '1');
        if (forceTrust) { body.append('force_trust', '1'); }

        fetch('<?= $adminBase ?>/apply', {
            method: 'POST',
            body: body,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'started' || data.status === 'completed') {
                    showCard();
                    render({percent: 0, phase_label: 'Starting...', phases: []});
                    poll();
                    return;
                }
                applyStarted = false;
                if (data.needsConfirm) {
                    document.getElementById('trust-confirm-version').textContent = (data.core && data.core.version) ? 'v' + data.core.version : '';
                    showModal(trustModal, trustBackdrop);
                    return;
                }
                if (data.blocked) {
                    document.getElementById('trust-blocked-warning').textContent = data.warning || 'No reason provided.';
                    showModal(blockedModal, blockedBackdrop);
                    return;
                }
                label.textContent = 'Could not start';
                detail.textContent = data.message || '';
                updateFailureCard();
            })
            .catch(function () {
                applyStarted = false;
                label.textContent = 'Could not start';
                detail.textContent = 'The request failed. Check the server logs.';
                updateFailureCard();
            });
    }

    function updateFailureCard() {
        showCard();
        bar.classList.remove('progress-bar-animated');
        bar.classList.add('bg-danger');
        applyBtn.disabled = false;
    }

    if (applyBtn) {
        applyBtn.addEventListener('click', openModal);
    }
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            closeModal();
            startApply(false);
        });
    }
    if (trustAnywayBtn) {
        trustAnywayBtn.addEventListener('click', function () {
            hideModal(trustModal, trustBackdrop);
            startApply(true);
        });
    }
    if (modal) {
        modal.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function (el) {
            el.addEventListener('click', closeModal);
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.style.display === 'block') { closeModal(); }
            if (event.key === 'Escape' && trustModal.style.display === 'block') { hideModal(trustModal, trustBackdrop); }
            if (event.key === 'Escape' && blockedModal.style.display === 'block') { hideModal(blockedModal, blockedBackdrop); }
        });
    }
    [[trustModal, trustBackdrop], [blockedModal, blockedBackdrop]].forEach(function (pair) {
        pair[0].querySelectorAll('[data-bs-dismiss="modal"]').forEach(function (el) {
            el.addEventListener('click', function () {
                hideModal(pair[0], pair[1]);
            });
        });
    });

    // ------------------------------------------------------------------
    // Settings save: inline confirmation, no page reload (v2 pattern).
    // ------------------------------------------------------------------
    var saveBtn  = document.getElementById('save-update-settings-btn');
    var savedMsg = document.getElementById('settings-saved-msg');
    if (saveBtn) {
        saveBtn.addEventListener('click', function () {
            saveBtn.disabled = true;
            var body = new FormData();
            body.append('_csrf_token', '<?= csrf_token() ?>');
            var checked = document.querySelector('input[name="auto_update"]:checked');
            body.append('auto_update', checked ? checked.value : '0');

            fetch('<?= $adminBase ?>/settings', {method: 'POST', body: body})
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    saveBtn.disabled = false;
                    if (data.status === 'ok') {
                        savedMsg.style.display = '';
                        setTimeout(function () { savedMsg.style.display = 'none'; }, 4000);
                    }
                })
                .catch(function () { saveBtn.disabled = false; });
        });
    }

    // ------------------------------------------------------------------
    // Trust recheck: per-row buttons hit the existing core recheck
    // endpoints (identity re-derived server-side) and swap the badge in
    // place. The badge markup mirrors the trust_badge() helper.
    // ------------------------------------------------------------------
    var csrfToken = '<?= csrf_token() ?>';

    function badgeHtml(status, warning) {
        var w = warning ? ' title="' + esc(warning) + '"' : '';
        switch (status) {
            case 'trusted':   return '<span class="badge bg-green-lt text-success"><i class="ti ti-shield-check me-1"></i>Trusted</span>';
            case 'known':     return '<span class="badge bg-azure-lt"' + w + '><i class="ti ti-shield me-1"></i>Known</span>';
            case 'malicious': return '<span class="badge bg-red-lt text-danger"' + w + '><i class="ti ti-alert-triangle me-1"></i>Malicious</span>';
            case 'unknown':   return '<span class="badge bg-yellow-lt text-yellow"><i class="ti ti-help me-1"></i>Unknown</span>';
            default:          return '<span class="badge bg-secondary-lt"><i class="ti ti-circle-dashed me-1"></i>Not checked</span>';
        }
    }

    document.querySelectorAll('[data-trust-recheck]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var kind = btn.getAttribute('data-trust-recheck');
            var cell = btn.closest('td').querySelector('[data-trust-cell]');
            var body = new FormData();
            body.append('_csrf_token', csrfToken);
            body.append(kind === 'theme' ? 'folder' : 'plugin', btn.getAttribute('data-handle'));

            btn.classList.add('disabled');
            fetch(kind === 'theme' ? '/admin/themes/recheck' : '/admin/plugins/recheck', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: body
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.ok && cell) {
                        cell.innerHTML = badgeHtml(data.status, data.warning);
                    }
                })
                .catch(function () {
                    // Leave the badge as it was.
                })
                .finally(function () {
                    btn.classList.remove('disabled');
                });
        });
    });

    if (running) {
        showCard();
        poll();
    }
})();
</script>
