<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Backup &amp; Export</h1>
</div>

<div class="row">

    <!-- Create Backup -->
    <div class="col-lg-5 mb-4">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Create Backup</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    Generates a <code>.zip</code> containing:
                </p>
                <ul class="text-muted small mb-3">
                    <li><strong>database.sql</strong> — full dump of all tables</li>
                    <li><strong>uploads/</strong> — all media files</li>
                </ul>
                <p class="text-muted small mb-4">
                    The file downloads directly to your browser. Large sites with many uploaded
                    files may take a moment — please be patient.
                </p>
                <form method="POST" action="<?= base_url('admin/backup/download') ?>"
                      id="backupForm">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary btn-block" id="backupBtn">
                        <i class="fas fa-download mr-1"></i> Create &amp; Download Backup
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Info -->
    <div class="col-lg-7 mb-4">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">About Backups</h6>
            </div>
            <div class="card-body text-muted small">
                <p><strong>What is included?</strong><br>
                    All database tables (posts, pages, users, settings, comments, media records,
                    navigation, themes, widgets, etc.) and every file in <code>writable/uploads/</code>.
                </p>
                <p><strong>What is not included?</strong><br>
                    Theme files, plugin files, and the application code itself — these are managed
                    via your git repository or file manager.
                </p>
                <p><strong>How do I restore?</strong><br>
                    Import <code>database.sql</code> via phpMyAdmin or the MySQL CLI, then copy the
                    <code>uploads/</code> folder back to <code>writable/uploads/</code>.
                </p>
                <p class="mb-0"><strong>Tip:</strong> Schedule a weekly cron job or set a
                    reminder to download a fresh backup before major changes.</p>
            </div>
        </div>
    </div>

</div>

<?php if (! empty($backups)): ?>
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Existing Backups in <code>writable/tmp/</code></h6>
        <small class="text-muted">These are temporary — download and delete once saved elsewhere.</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Filename</th>
                        <th>Size</th>
                        <th>Created</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($backups as $backup): ?>
                    <tr>
                        <td><i class="fas fa-file-archive text-secondary mr-1"></i> <?= esc($backup['filename']) ?></td>
                        <td><?= esc($backup['size']) ?></td>
                        <td><?= esc($backup['created']) ?></td>
                        <td class="text-right">
                            <form method="POST" action="<?= base_url('admin/backup/delete') ?>"
                                  class="d-inline"
                                  onsubmit="return confirm('Delete this backup file?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="filename" value="<?= esc($backup['filename']) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php $extra_scripts = <<<'SCRIPT'
<script>
document.getElementById('backupForm').addEventListener('submit', function () {
    var btn = document.getElementById('backupBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Generating backup…';
    // Re-enable after 60s in case the download completes or an error occurs
    setTimeout(function () {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-download mr-1"></i> Create &amp; Download Backup';
    }, 60000);
});
</script>
SCRIPT;
?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
