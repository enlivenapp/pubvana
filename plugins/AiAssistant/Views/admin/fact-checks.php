<?php
/**
 * AI Assistant Fact Checking: terms acceptance, on/off, report history.
 *
 * @var string $pageTitle
 * @var string $adminBase  admin URL base for this plugin
 * @var array  $prompt          FactCheckService::currentPrompt() row
 * @var bool   $enabled
 * @var string|null $acceptedAt
 * @var string|null $acceptedVersion
 * @var bool   $termsCurrent
 * @var list<string> $blockers
 * @var list<array<string, mixed>> $reports  serializeReport() rows
 * @var int    $total
 * @var array<string, string> $verdictTones  verdict => badge tone
 */
?>

<div class="d-flex justify-content-end mb-3">
    <a href="<?= $adminBase ?>/manage" class="btn btn-outline-secondary me-2">
        <i class="ti ti-key me-1"></i> Manage keys
    </a>
    <a href="<?= $adminBase ?>/help" class="btn btn-primary">
        <i class="ti ti-book-2 me-1"></i> Quick Start &amp; Help
    </a>
</div>

<div class="alert alert-info" role="alert">
    <div class="d-flex">
        <i class="ti ti-certificate icon alert-icon"></i>
        <div>
            Fact checking is performed by <strong>your</strong> AI assistant (CLI, IDE, or desktop) through the
            same API key it already uses. The assistant fetches a versioned integrity prompt from this site,
            checks the content against real sources, and submits a structured report. You accept the terms once
            per prompt version, and every report lands here. The service refuses to run until you accept, and
            again whenever the prompt is updated.
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">Fact-Checking Terms</h3>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <span class="badge bg-blue-lt">Prompt v<?= htmlspecialchars($prompt['version']) ?></span>
                    <span class="badge bg-secondary-lt">source: <?= htmlspecialchars($prompt['source']) ?></span>
                    <?php if (!$termsCurrent && $acceptedVersion !== null): ?>
                        <span class="badge bg-warning">update available: v<?= htmlspecialchars($prompt['version']) ?> accepted: v<?= htmlspecialchars($acceptedVersion) ?></span>
                    <?php endif; ?>
                </div>
                <h4 class="mb-2"><?= htmlspecialchars($prompt['title']) ?></h4>
                <p class="text-secondary"><?= htmlspecialchars($prompt['summary']) ?></p>

                <p class="collapse" id="full-terms">
                    <span class="d-block p-3 bg-light rounded small" style="white-space: pre-wrap;"><?= htmlspecialchars($prompt['text']) ?></span>
                </p>
                <a class="small" data-bs-toggle="collapse" href="#full-terms" role="button" aria-expanded="false" aria-controls="full-terms">
                    Show / hide the full prompt text
                </a>

                <?php if ($acceptedVersion !== null): ?>
                    <div class="alert alert-success mt-3 mb-0" role="alert">
                        <i class="ti ti-check icon alert-icon"></i>
                        Terms accepted: v<?= htmlspecialchars($acceptedVersion) ?><?= $acceptedAt !== null ? ' on ' . date('M j, Y g:ia', strtotime($acceptedAt)) : '' ?>
                    </div>
                <?php endif; ?>

                <?php if (!$termsCurrent): ?>
                    <form method="POST" action="<?= $adminBase ?>/fact-checks/terms" class="mt-3">
                        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" name="agree" value="1" required>
                            <span class="form-check-label">
                                I agree to the terms above. Fact checking may only be used to establish
                                accurate information; attempts to steer or circumvent the prompt void the
                                service.
                            </span>
                        </label>
                        <button class="btn btn-primary mt-2">
                            <?= $acceptedVersion === null ? 'Accept terms' : 'Re-accept updated terms' ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title">Service</h3>
                <?php if ($enabled): ?>
                    <span class="badge bg-success-lt">On</span>
                <?php else: ?>
                    <span class="badge bg-secondary-lt">Off</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($enabled): ?>
                    <p class="text-secondary small">
                        Every enabled API key can read and submit fact checks. Turning it off makes the
                        fact-check endpoints refuse every request, and the public block renders nothing.
                    </p>
                    <?php if ($blockers !== []): ?>
                        <div class="alert alert-warning" role="alert">
                            <i class="ti ti-alert-triangle icon alert-icon"></i>
                            <?= htmlspecialchars(implode(' ', $blockers)) ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="<?= $adminBase ?>/fact-checks/toggle">
                        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="enable" value="0">
                        <button class="btn btn-outline-danger">Turn fact checking off</button>
                    </form>
                <?php else: ?>
                    <p class="text-secondary small">
                        Turning it on requires the terms above to be accepted for the current prompt version,
                        and at least one enabled API key.
                    </p>
                    <?php if ($blockers !== []): ?>
                        <ul class="small text-secondary ps-3">
                            <?php foreach ($blockers as $blocker): ?>
                                <li><?= htmlspecialchars($blocker) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <form method="POST" action="<?= $adminBase ?>/fact-checks/toggle">
                        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="enable" value="1">
                        <button class="btn btn-primary" <?= $blockers !== [] ? 'disabled' : '' ?>>Turn fact checking on</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Report History <span class="text-secondary fw-normal">(<?= (int) $total ?> total, latest <?= count($reports) ?>)</span></h3>
    </div>
    <?php if (empty($reports)): ?>
        <div class="card-body">
            <p class="text-center text-secondary py-3 mb-0">No fact checks yet. Once the service is on, ask your AI assistant to check a post or page.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Content</th>
                        <th>Verdict</th>
                        <th>Claims</th>
                        <th>Interference</th>
                        <th>Key</th>
                        <th class="w-1">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td class="text-secondary text-nowrap"><?= date('M j, Y g:ia', strtotime((string) $report['created_at'])) ?></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars((string) $report['content_title']) ?></div>
                                <span class="badge bg-secondary-lt"><?= htmlspecialchars((string) $report['content_type']) ?></span>
                                <?php if ($report['stale']): ?>
                                    <span class="badge bg-warning-lt" title="The content changed after this check was made">stale</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= htmlspecialchars($verdictTones[$report['overall_verdict']] ?? 'secondary') ?>-lt">
                                    <?= htmlspecialchars((string) $report['overall_verdict_label']) ?>
                                </span>
                            </td>
                            <td class="text-secondary small">
                                <?= (int) $report['counts']['supported'] ?> supported,
                                <?= (int) $report['counts']['partially_supported'] ?> partial,
                                <?= (int) $report['counts']['refuted'] ?> refuted,
                                <?= (int) $report['counts']['unverifiable'] ?> unverif.,
                                <?= (int) $report['counts']['opinions'] ?> opinions
                            </td>
                            <td>
                                <?php if ($report['prompt_interference']): ?>
                                    <span class="badge bg-danger-lt">flagged</span>
                                <?php else: ?>
                                    <span class="text-secondary">&ndash;</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-secondary"><?= $report['key_name'] !== null ? htmlspecialchars((string) $report['key_name']) : '<em class="text-secondary">unknown</em>' ?></td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= $adminBase ?>/fact-checks/<?= (int) $report['id'] ?>">View</a>
                                    <form method="POST" action="<?= $adminBase ?>/fact-checks/<?= (int) $report['id'] ?>/delete" class="d-inline"
                                          onsubmit="return confirm('Delete this fact-check report?')">
                                        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
