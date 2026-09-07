<?php
/**
 * Site Health detail page.
 *
 * @var string $pageTitle
 * @var array  $results
 * @var array  $grouped
 * @var array  $summary
 * @var string $cachedAt
 * @var array  $categories
 * @var string $adminBase
 */

$statusBadge = function (string $status): string {
    return match ($status) {
        'critical' => '<span class="badge bg-red-lt">Critical</span>',
        'warning'  => '<span class="badge bg-yellow-lt">Warning</span>',
        default    => '<span class="badge bg-success-lt">Pass</span>',
    };
};

$statusIcon = function (string $status): string {
    return match ($status) {
        'critical' => '<i class="ti ti-circle-x text-danger"></i>',
        'warning'  => '<i class="ti ti-alert-triangle text-warning"></i>',
        default    => '<i class="ti ti-circle-check text-success"></i>',
    };
};

$overallTone = match ($summary['overall'] ?? 'good') {
    'critical' => 'danger',
    'warning'  => 'warning',
    default    => 'success',
};

$overallIcon = match ($summary['overall'] ?? 'good') {
    'critical' => 'ti-alert-circle',
    'warning'  => 'ti-alert-triangle',
    default    => 'ti-circle-check',
};

$overallLabel = match ($summary['overall'] ?? 'good') {
    'critical' => 'Critical issues found',
    'warning'  => 'Some items need attention',
    default    => 'Everything looks good',
};
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="page-title mb-1">
            <i class="ti <?= $overallIcon ?> text-<?= $overallTone ?> me-2"></i>
            <?= htmlspecialchars($overallLabel) ?>
        </h2>
        <div class="text-secondary">
            <?= (int) $summary['pass'] ?> passed, <?= (int) $summary['warning'] ?> warnings, <?= (int) $summary['critical'] ?> critical
            <?php if ($cachedAt): ?>
                <span class="mx-1">|</span> Last checked: <?= htmlspecialchars($cachedAt) ?>
            <?php endif; ?>
        </div>
    </div>
    <form method="post" action="<?= $adminBase ?>/rerun">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
        <button type="submit" class="btn btn-outline-primary">
            <i class="ti ti-refresh me-1"></i> Re-run Checks
        </button>
    </form>
</div>

<div class="alert alert-info d-flex align-items-center mb-4" role="alert">
    <i class="ti ti-info-circle me-2"></i>
    <div>
        Results are cached and updated only when you click <strong>Re-run Checks</strong>.
    </div>
</div>

<!-- Summary cards -->
<div class="row row-cards mb-4">
    <div class="col-sm-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="h1 text-success mb-0"><?= (int) $summary['pass'] ?></div>
                <div class="text-secondary">Passed</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="h1 text-warning mb-0"><?= (int) $summary['warning'] ?></div>
                <div class="text-secondary">Warnings</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="h1 text-danger mb-0"><?= (int) $summary['critical'] ?></div>
                <div class="text-secondary">Critical</div>
            </div>
        </div>
    </div>
</div>

<!-- Checks by category -->
<?php foreach ($categories as $catKey => $catMeta): ?>
    <?php if (empty($grouped[$catKey])) continue; ?>
    <div class="card mb-4" id="<?= htmlspecialchars($catKey) ?>">
        <div class="card-header">
            <h3 class="card-title">
                <i class="ti <?= htmlspecialchars($catMeta['icon']) ?> me-2"></i>
                <?= htmlspecialchars($catMeta['label']) ?>
            </h3>
        </div>
        <div class="list-group list-group-flush">
            <?php foreach ($grouped[$catKey] as $check): ?>
                <div class="list-group-item" id="<?= htmlspecialchars($check['id']) ?>">
                    <div class="d-flex align-items-start gap-3">
                        <div class="mt-1">
                            <?= $statusIcon($check['status']) ?>
                        </div>
                        <div class="flex-fill">
                            <div class="d-flex align-items-center justify-content-between">
                                <strong><?= htmlspecialchars($check['name']) ?></strong>
                                <?= $statusBadge($check['status']) ?>
                            </div>
                            <div class="text-secondary mt-1">
                                <?= htmlspecialchars($check['message']) ?>
                            </div>
                            <?php if (!empty($check['remediation']) && $check['status'] !== 'pass'): ?>
                                <div class="mt-2 p-2 bg-light rounded">
                                    <small class="text-primary">
                                        <i class="ti ti-bulb me-1"></i>
                                        <?= htmlspecialchars($check['remediation']) ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
