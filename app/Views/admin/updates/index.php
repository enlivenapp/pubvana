<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Updates</h1>
    <form method="POST" action="<?= base_url('admin/updates/check') ?>">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-sync-alt fa-sm"></i> Force Re-check
        </button>
    </form>
</div>

<div class="row">
    <!-- Status column -->
    <div class="col-lg-8">

        <?php if (!empty($update['error'])): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                <strong>Could not contact GitHub:</strong> <?= esc($update['error']) ?>
            </div>

        <?php elseif (!empty($update['available'])): ?>
            <div class="alert alert-warning">
                <i class="fas fa-arrow-circle-up mr-1"></i>
                <strong>Pubvana <?= esc($update['latest_version']) ?> is available!</strong>
                You are running <?= esc($update['current_version']) ?>.
            </div>

            <div class="mb-3">
                <?php if (!empty($update['release_url'])): ?>
                <a href="<?= esc($update['release_url']) ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary mr-2">
                    <i class="fas fa-tag fa-sm"></i> View Release on GitHub
                </a>
                <?php endif; ?>
                <?php if (!empty($update['zipball_url'])): ?>
                <a href="<?= esc($update['zipball_url']) ?>" class="btn btn-warning">
                    <i class="fas fa-download fa-sm"></i> Download ZIP
                </a>
                <?php endif; ?>
            </div>

            <?php if (!empty($update['release_notes'])): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Release Notes</h6>
                </div>
                <div class="card-body">
                    <pre style="white-space:pre-wrap;font-family:inherit;margin:0"><?= esc($update['release_notes']) ?></pre>
                </div>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle mr-1"></i>
                <strong>Pubvana is up to date.</strong>
                You are running version <?= esc($update['current_version']) ?>.
            </div>
        <?php endif; ?>

    </div>

    <!-- How to Apply column -->
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">How to Apply an Update</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Web-based auto-apply is not available for safety reasons. Use one of these methods:</p>

                <h6 class="font-weight-bold">Option 1 — CLI (recommended)</h6>
                <pre class="bg-light p-2 rounded small" style="white-space:pre-wrap">php spark pubvana:update</pre>
                <p class="small text-muted mb-3">Downloads, extracts, copies <code>app/</code> + <code>public/</code>, then runs migrations. Your <code>.env</code> and config files are preserved.</p>

                <h6 class="font-weight-bold">Option 2 — Manual</h6>
                <ol class="small text-muted pl-3 mb-0">
                    <li>Download the ZIP above</li>
                    <li>Extract it on your machine</li>
                    <li>Upload <code>app/</code> and <code>public/</code> (skip <code>.env</code>, <code>App.php</code>, <code>Database.php</code>)</li>
                    <li>Run <code>php spark migrate --all</code></li>
                    <li>Clear cache: <code>php spark cache:clear</code></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
