<?php
/**
 * Admin backup listing with create/download/restore/delete actions
 * and progress bar for background operations.
 *
 * @var array   $backups   List of backup entries from BackupService::listBackups()
 * @var bool    $is_locked Whether an operation is currently running
 * @var string  $adminBase Full admin URL base for this plugin
 *
 * @package  Pubvana\Plugins\Backups
 * @copyright 2026 enlivenapp
 * @license  MIT
 */
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="h4 mb-0">Backups</h2>
    <button type="button" class="btn btn-primary btn-sm" id="createBackupBtn" <?= !empty($is_locked) ? 'disabled' : '' ?>>
        <i class="ti ti-plus me-1"></i> Create Backup
    </button>
</div>

<!-- Progress bar (hidden by default) -->
<div id="backup-progress" class="card mb-4" style="display:none;">
    <div class="card-body">
        <h6 class="fw-bold" id="progress-label">Starting...</h6>
        <div class="progress mb-2">
            <div class="progress-bar progress-bar-striped progress-bar-animated" id="progress-bar"
                 role="progressbar" style="width: 0%"></div>
        </div>
        <small class="text-muted" id="progress-detail"></small>
    </div>
</div>

<?php if (empty($backups)): ?>
    <div class="alert alert-info">
        <i class="ti ti-info-circle me-1"></i> No backups yet. Click "Create Backup" to create your first one.
    </div>
<?php else: ?>
<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="m-0 fw-bold">Available Backups</h6>
        <small class="text-muted">Oldest backups are removed automatically.</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Filename</th>
                    <th>Trigger</th>
                    <th>Size</th>
                    <th>Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($backups as $backup): ?>
                <tr>
                    <td><i class="ti ti-file-zip text-secondary me-1"></i> <?= htmlspecialchars($backup['filename']) ?></td>
                    <td><span class="badge bg-secondary-lt"><?= htmlspecialchars($backup['meta']['trigger'] ?? '-') ?></span></td>
                    <td><?= htmlspecialchars($backup['size']) ?></td>
                    <td><?= htmlspecialchars($backup['created']) ?></td>
                    <td class="text-end text-nowrap">
                        <a href="<?= $adminBase ?>/download/<?= htmlspecialchars($backup['filename']) ?>"
                           class="btn btn-sm btn-outline-primary" title="Download">
                            <i class="ti ti-download"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-warning restore-btn"
                                data-filename="<?= htmlspecialchars($backup['filename']) ?>"
                                title="Restore">
                            <i class="ti ti-rotate-2"></i>
                        </button>
                        <form method="POST"
                              action="<?= $adminBase ?>/<?= htmlspecialchars($backup['filename']) ?>/delete"
                              class="d-inline"
                              onsubmit="return confirm('Delete this backup?')">
                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                <i class="ti ti-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
(function() {
    var btn       = document.getElementById('createBackupBtn');
    var prog      = document.getElementById('backup-progress');
    var createUrl = '<?= $adminBase ?>/create';
    var statusUrl = '<?= $adminBase ?>/status';

    // Create backup
    btn.addEventListener('click', function() {
        btn.disabled = true;
        btn.innerHTML = '<i class="ti ti-loader ti-spin me-1"></i> Starting...';
        prog.style.display = 'block';

        var body = new FormData();
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) {
            body.append('_csrf_token', csrfMeta.content);
        }

        fetch(createUrl, { method: 'POST', body: body })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.status === 'error') {
                    showError(data.message || 'Unknown error');
                    return;
                }
                if (data.status === 'completed') {
                    showComplete();
                    return;
                }
                pollStatus();
            })
            .catch(function(err) {
                showError('Request failed: ' + err.message);
            });
    });

    // Restore buttons
    document.querySelectorAll('.restore-btn').forEach(function(el) {
        el.addEventListener('click', function() {
            var filename = this.dataset.filename;
            if (!confirm('Restore from this backup? A snapshot of the current state will be taken first.')) {
                return;
            }

            prog.style.display = 'block';
            document.getElementById('progress-label').textContent = 'Starting restore...';

            var body = new FormData();
            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) {
                body.append('_csrf_token', csrfMeta.content);
            }

            fetch('<?= $adminBase ?>/restore/' + encodeURIComponent(filename), { method: 'POST', body: body })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.status === 'error') {
                        showError(data.message || 'Unknown error');
                        return;
                    }
                    if (data.status === 'completed') {
                        showComplete();
                        return;
                    }
                    pollStatus();
                })
                .catch(function(err) {
                    showError('Request failed: ' + err.message);
                });
        });
    });

    function pollStatus() {
        var interval = setInterval(function() {
            fetch(statusUrl)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.status === 'in_progress') {
                        var pct = data.steps_total > 0
                            ? Math.round((data.steps_completed / data.steps_total) * 100)
                            : 0;
                        document.getElementById('progress-bar').style.width = pct + '%';
                        document.getElementById('progress-label').textContent = data.step_label || 'Working...';
                        document.getElementById('progress-detail').textContent = data.detail || '';
                    } else if (data.status === 'completed') {
                        clearInterval(interval);
                        showComplete();
                    } else if (data.status === 'error') {
                        clearInterval(interval);
                        showError(data.error || 'Unknown error');
                    }
                });
        }, 500);
    }

    function showComplete() {
        document.getElementById('progress-bar').style.width = '100%';
        document.getElementById('progress-bar').classList.remove('progress-bar-animated');
        document.getElementById('progress-bar').classList.add('bg-success');
        document.getElementById('progress-label').textContent = 'Complete!';
        setTimeout(function() { location.reload(); }, 1500);
    }

    function showError(msg) {
        document.getElementById('progress-label').textContent = 'Error: ' + msg;
        document.getElementById('progress-bar').classList.remove('progress-bar-animated');
        document.getElementById('progress-bar').classList.add('bg-danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-plus me-1"></i> Create Backup';
    }
})();
</script>