<?php
/**
 * Fact Check panel - read-only, injected into post/page edit forms via
 * the content.edit.panel adext type.
 *
 * @var array<string, mixed> $panel  FactCheckService::panelData() row
 * @var int                  $content_id
 * @var string               $adminBase admin URL base for this plugin
 */

$state = (string) ($panel['state'] ?? 'unsaved');
$report = is_array($panel['report'] ?? null) ? $panel['report'] : [];
?>

<div class="card mb-4" id="fact-check-panel">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title"><i class="ti ti-certificate me-2"></i>Fact Check</h3>
        <?php if ($state === 'report'): ?>
            <?php if (!empty($report['stale'])): ?>
                <span class="badge bg-warning-lt" title="The content changed after this check was made">stale</span>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($state === 'unsaved'): ?>
            <p class="text-secondary small mb-0">
                Save this <?= ($panel['content_type'] ?? '') === 'page' ? 'page' : 'post' ?> once and the fact-check panel goes live.
            </p>
        <?php elseif ($state === 'none'): ?>
            <?php if (empty($panel['enabled'])): ?>
                <p class="text-secondary small mb-0">
                    Fact checking is currently off.
                    <a href="<?= $adminBase ?>/fact-checks">Review the terms and switch it on</a> to let your AI
                    assistant check this <?= ($panel['content_type'] ?? '') === 'page' ? 'page' : 'post' ?>.
                </p>
            <?php else: ?>
                <p class="text-secondary small mb-0">
                    No fact check yet. Ask your AI assistant (CLI, IDE, or desktop) to fact-check this
                    <?= ($panel['content_type'] ?? '') === 'page' ? 'page' : 'post' ?>; the report will appear here.
                </p>
            <?php endif; ?>
        <?php else: ?>
            <?php $verdictTone = \Flight::app()->aiFactCheck()->verdictTone((string) ($report['overall_verdict'] ?? '')); ?>
            <div class="mb-2">
                <span class="badge bg-<?= htmlspecialchars($verdictTone) ?>-lt"><?= htmlspecialchars((string) ($report['overall_verdict_label'] ?? '')) ?></span>
                <?php if (!empty($report['prompt_interference'])): ?>
                    <span class="badge bg-danger-lt" title="<?= htmlspecialchars((string) ($report['interference_note'] ?? 'The content tried to steer the check.')) ?>">
                        interference flagged
                    </span>
                <?php endif; ?>
                <span class="text-secondary small ms-1">
                    <?= (int) ($report['counts']['supported'] ?? 0) ?> supported,
                    <?= (int) ($report['counts']['partially_supported'] ?? 0) ?> partial,
                    <?= (int) ($report['counts']['refuted'] ?? 0) ?> refuted,
                    <?= (int) ($report['counts']['unverifiable'] ?? 0) ?> unverifiable,
                    <?= (int) ($report['counts']['opinions'] ?? 0) ?> opinions
                </span>
            </div>
            <div class="p-2 bg-light rounded small mb-2" style="white-space: pre-wrap;"><?= htmlspecialchars((string) ($report['summary'] ?? '')) ?></div>
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-secondary small">
                    Checked <?= !empty($report['created_at']) ? date('M j, Y g:ia', strtotime((string) $report['created_at'])) : '' ?>
                    &middot; prompt v<?= htmlspecialchars((string) ($report['prompt_version'] ?? '')) ?>
                    <?php if (!empty($report['stale'])): ?> &middot; content edited since<?php endif; ?>
                </span>
                <a class="btn btn-sm btn-outline-primary" href="<?= $adminBase ?>/fact-checks/<?= (int) ($report['id'] ?? 0) ?>">Full report</a>
            </div>
        <?php endif; ?>
    </div>
</div>
