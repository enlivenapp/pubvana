<?php
/**
 * One fact-check report in full.
 *
 * @var string $pageTitle
 * @var string $adminBase admin URL base for this plugin
 * @var array<string, mixed> $report serializeReport() row
 */

$verdictTone = \Flight::app()->aiFactCheck()->verdictTone((string) $report['overall_verdict']);
$prefix = \Flight::app()->pluginLoader()->routePrefix($report['content_type'] === 'post' ? 'pubvana/blog' : 'pubvana/pages');
$contentUrl = '/' . trim((string) $prefix, '/') . '/';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?= $adminBase ?>/fact-checks" class="btn btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i> All reports
    </a>
    <form method="POST" action="<?= $adminBase ?>/fact-checks/<?= (int) $report['id'] ?>/delete"
          onsubmit="return confirm('Delete this fact-check report?')">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
        <button class="btn btn-outline-danger"><i class="ti ti-trash me-1"></i> Delete report</button>
    </form>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">
            <?= htmlspecialchars((string) $report['content_title']) ?>
        </h3>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <span class="badge bg-secondary-lt"><?= htmlspecialchars((string) $report['content_type']) ?> #<?= (int) $report['content_id'] ?></span>
            <a class="small" href="<?= $contentUrl . rawurlencode((string) $report['content_slug']) ?>" target="_blank" rel="noopener">
                <?= htmlspecialchars((string) $report['content_slug']) ?> <i class="ti ti-external-link"></i>
            </a>
            <?php if ($report['stale']): ?>
                <span class="badge bg-warning-lt" title="The content changed after this check was made">stale: content edited after this check</span>
            <?php endif; ?>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="text-secondary small text-uppercase">Overall verdict</div>
                <span class="badge bg-<?= htmlspecialchars($verdictTone) ?>-lt fs-4 mt-1"><?= htmlspecialchars((string) $report['overall_verdict_label']) ?></span>
            </div>
            <div class="col-md-4">
                <div class="text-secondary small text-uppercase">Claim tally</div>
                <div class="mt-1">
                    <span class="badge bg-success-lt me-1"><?= (int) $report['counts']['supported'] ?> supported</span>
                    <span class="badge bg-warning-lt me-1"><?= (int) $report['counts']['partially_supported'] ?> partial</span>
                    <span class="badge bg-danger-lt me-1"><?= (int) $report['counts']['refuted'] ?> refuted</span>
                    <span class="badge bg-secondary-lt me-1"><?= (int) $report['counts']['unverifiable'] ?> unverif.</span>
                    <span class="badge bg-blue-lt"><?= (int) $report['counts']['opinions'] ?> opinions</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-secondary small text-uppercase">Checked</div>
                <div><?= date('M j, Y g:ia', strtotime((string) $report['created_at'])) ?></div>
                <div class="text-secondary small">
                    by <?= $report['key_name'] !== null ? htmlspecialchars((string) $report['key_name']) : 'unknown key' ?>
                    &middot; prompt v<?= htmlspecialchars((string) $report['prompt_version']) ?>
                </div>
            </div>
        </div>

        <?php if ($report['prompt_interference']): ?>
            <div class="alert alert-danger" role="alert">
                <div class="d-flex">
                    <i class="ti ti-shield-off icon alert-icon"></i>
                    <div>
                        <strong>Prompt interference flagged.</strong> The checked content attempted to steer
                        the fact check. The checker reported it and continued under the Pubvana terms.
                        <?php if ($report['interference_note'] !== null): ?>
                            <div class="mt-2 p-2 bg-light rounded small" style="white-space: pre-wrap;"><?= htmlspecialchars((string) $report['interference_note']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <h4 class="mt-4 mb-2">Findings</h4>
        <div class="p-3 bg-light rounded" style="white-space: pre-wrap;"><?= htmlspecialchars((string) $report['summary']) ?></div>
    </div>
</div>

<?php if (!empty($report['claims'])): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Claims <span class="text-secondary fw-normal">(<?= count($report['claims']) ?>)</span></h3>
        </div>
        <div class="list-group list-group-flush">
            <?php foreach ($report['claims'] as $claim): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div class="fw-semibold"><?= htmlspecialchars((string) $claim['text']) ?></div>
                        <?php if ($claim['kind'] === 'opinion'): ?>
                            <span class="badge bg-blue-lt text-nowrap">opinion</span>
                        <?php else: ?>
                            <span class="badge bg-<?= htmlspecialchars(\Flight::app()->aiFactCheck()->verdictTone((string) ($claim['verdict'] ?? ''))) ?>-lt text-nowrap">
                                <?= htmlspecialchars((string) ($claim['verdict_label'] ?? '')) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($claim['kind'] === 'opinion' && !empty($claim['determination'])): ?>
                        <div class="small mb-1"><span class="text-secondary">Determination:</span> <?= htmlspecialchars((string) $claim['determination']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($claim['explanation'])): ?>
                        <div class="small text-secondary mb-1" style="white-space: pre-wrap;"><?= htmlspecialchars((string) $claim['explanation']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($claim['correction'])): ?>
                        <div class="small mb-1"><span class="text-danger fw-semibold">Suggested correction:</span> <?= htmlspecialchars((string) $claim['correction']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($claim['sources'])): ?>
                        <div class="small">
                            <span class="text-secondary">Sources:</span>
                            <?php foreach ($claim['sources'] as $source): ?>
                                <a href="<?= htmlspecialchars((string) $source) ?>" target="_blank" rel="noopener nofollow" class="me-2 text-break">
                                    <?= htmlspecialchars((string) $source) ?> <i class="ti ti-external-link"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
