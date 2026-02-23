<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Import from WordPress</h1>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<?php if (!empty($results)): ?>
    <?php $dryLabel = !empty($dry_run) ? ' (Dry Run — no data written)' : ''; ?>
    <div class="alert alert-<?= empty($results['errors']) ? 'success' : 'warning' ?>">
        <h5 class="font-weight-bold">Import Complete<?= esc($dryLabel) ?></h5>
        <div class="row mt-3">
            <?php foreach (['authors', 'categories', 'tags', 'posts', 'pages', 'comments'] as $type): ?>
            <div class="col-md-2 text-center mb-3">
                <h4 class="font-weight-bold text-primary"><?= $results[$type]['created'] ?></h4>
                <small class="text-muted"><?= ucfirst($type) ?> created</small><br>
                <small class="text-muted"><?= $results[$type]['skipped'] ?> skipped</small>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($results['errors'])): ?>
            <hr>
            <h6>Errors:</h6>
            <ul>
                <?php foreach ($results['errors'] as $err): ?>
                    <li><?= esc($err) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Upload WordPress Export</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Export your WordPress content from <strong>Tools → Export → All content</strong> and upload the <code>.xml</code> file here.
                    Pubvana will import posts, pages, categories, tags, authors, and comments.
                </p>

                <form method="POST" action="<?= base_url('admin/import') ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label class="font-weight-bold">WXR Export File (.xml)</label>
                        <input type="file" name="wxr_file" class="form-control-file" accept=".xml" required>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" name="dry_run" id="dry_run" class="form-check-input" value="1">
                        <label class="form-check-label" for="dry_run">
                            <strong>Dry run</strong> — preview what would be imported without writing to the database
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload mr-1"></i> Upload &amp; Import
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">CLI Import</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-2">You can also run the importer from the command line:</p>
                <pre class="bg-light p-3 rounded"><code>php spark wp:import /path/to/export.xml</code></pre>
                <pre class="bg-light p-3 rounded"><code>php spark wp:import /path/to/export.xml --dry-run</code></pre>
                <p class="text-muted small mt-2">The <code>--dry-run</code> flag shows what would be imported without writing to the database.</p>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">What Gets Imported</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled small text-muted mb-0">
                    <li><i class="fas fa-check text-success mr-1"></i> Posts (title, content, excerpt, slug, status)</li>
                    <li><i class="fas fa-check text-success mr-1"></i> Pages</li>
                    <li><i class="fas fa-check text-success mr-1"></i> Categories (with hierarchy)</li>
                    <li><i class="fas fa-check text-success mr-1"></i> Tags</li>
                    <li><i class="fas fa-check text-success mr-1"></i> Authors (created as subscriber accounts)</li>
                    <li><i class="fas fa-check text-success mr-1"></i> Comments</li>
                    <li><i class="fas fa-times text-muted mr-1"></i> Media files (URLs preserved in content)</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
