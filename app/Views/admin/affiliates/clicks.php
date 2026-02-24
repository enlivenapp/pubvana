<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        Clicks — <span class="text-primary"><?= esc($link->name) ?></span>
    </h1>
    <a href="<?= base_url('admin/affiliates') ?>" class="btn btn-secondary btn-sm shadow-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back to Links
    </a>
</div>

<!-- Stats row -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Clicks</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($total) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-mouse-pointer fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-6 col-md-6">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Destination</div>
                        <div class="small text-truncate text-gray-800">
                            <a href="<?= esc($link->destination_url) ?>" target="_blank" rel="noopener">
                                <?= esc($link->destination_url) ?>
                            </a>
                        </div>
                    </div>
                    <div class="col-auto"><i class="fas fa-external-link-alt fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-left-<?= $link->is_active ? 'success' : 'secondary' ?> shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1
                            text-<?= $link->is_active ? 'success' : 'secondary' ?>">Status</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= $link->is_active ? 'Active' : 'Inactive' ?>
                        </div>
                    </div>
                    <div class="col-auto"><i class="fas fa-circle fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Click Log</h6>
        <span class="text-muted small">IPs are stored as SHA-256 hashes — no raw PII recorded.</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($clicks)): ?>
            <div class="p-4 text-muted text-center">No clicks recorded yet.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Time</th>
                        <th>IP Hash</th>
                        <th>Referrer</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($clicks as $click): ?>
                    <tr>
                        <td class="text-muted small"><?= esc($click->created_at) ?></td>
                        <td class="text-monospace small"><?= esc(substr($click->ip_hash, 0, 16)) ?>…</td>
                        <td class="small">
                            <?php if ($click->referrer): ?>
                                <span title="<?= esc($click->referrer) ?>">
                                    <?= esc(parse_url($click->referrer, PHP_URL_HOST) ?: $click->referrer) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">Direct</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php if (isset($pager) && $pager): ?>
    <div class="card-footer">
        <?= $pager->links('default', 'bootstrap_full') ?>
    </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
