<?php
/**
 * Broken Links admin page.
 *
 * @var string $pageTitle
 * @var array  $grouped
 * @var int    $total
 * @var bool   $showDismissed
 * @var string $adminBase
 */

$editUrl = function (string $sourceType, int $sourceId): string {
    return $sourceType === 'post'
        ? '/admin/blog/' . $sourceId . '/edit'
        : '/admin/pages/' . $sourceId . '/edit';
};
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= htmlspecialchars($pageTitle) ?></h1>
    <div>
        <?php if ($showDismissed): ?>
            <a href="<?= $adminBase ?>" class="btn btn-sm btn-outline-secondary me-2">
                <i class="ti ti-eye me-1"></i> Hide Dismissed
            </a>
        <?php else: ?>
            <a href="<?= $adminBase ?>?dismissed=1" class="btn btn-sm btn-outline-secondary me-2">
                <i class="ti ti-eye-off me-1"></i> Show Dismissed
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Scan card -->
<div class="card shadow mb-4">
    <div class="card-body py-3 d-flex align-items-center justify-content-between flex-wrap">
        <div class="d-flex align-items-center">
            <form method="POST" action="<?= $adminBase ?>/scan" class="d-inline me-3">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="ti ti-search me-1"></i> Run Scan
                </button>
            </form>
            <span class="badge bg-<?= $total > 0 ? 'red-lt' : 'success-lt' ?> px-3 py-2">
                <?= $total ?> issue<?= $total !== 1 ? 's' : '' ?>
            </span>
        </div>
    </div>
</div>

<?php if (empty($grouped)): ?>
    <div class="card shadow mb-4">
        <div class="card-body text-center text-muted py-5">
            <i class="ti ti-circle-check fa-3x text-success mb-3 d-block"></i>
            No broken links detected.
        </div>
    </div>
<?php else: ?>
    <?php foreach ($grouped as $group): ?>
    <div class="card shadow mb-3">
        <div class="card-header py-2 d-flex align-items-center justify-content-between">
            <div>
                <span class="badge bg-<?= $group['source_type'] === 'post' ? 'blue-lt' : 'secondary-lt' ?> me-2">
                    <?= $group['source_type'] === 'post' ? 'Post' : 'Page' ?>
                </span>
                <a href="<?= htmlspecialchars($editUrl($group['source_type'], $group['source_id'])) ?>"
                   class="fw-bold text-dark">
                    <?= htmlspecialchars($group['source_title']) ?>
                </a>
            </div>
            <span class="badge bg-red-lt">
                <?= count($group['links']) ?> broken
            </span>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>URL</th>
                        <th class="w-10 text-center">Status</th>
                        <th class="w-15">Error</th>
                        <th class="w-15 text-muted small">Last Checked</th>
                        <th class="w-15 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($group['links'] as $link): ?>
                    <tr class="<?= $link->dismissed ? 'text-muted' : '' ?>">
                        <td class="small">
                            <a href="<?= htmlspecialchars($link->url) ?>"
                               target="_blank" rel="noopener"
                               class="text-truncate d-block"
                               title="<?= htmlspecialchars($link->url) ?>">
                                <?= htmlspecialchars($link->url) ?>
                            </a>
                        </td>
                        <td class="text-center">
                            <?php if ($link->http_status !== null): ?>
                                <span class="badge bg-<?= $link->http_status >= 400 ? 'red-lt' : 'yellow-lt' ?>">
                                    <?= $link->http_status ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary-lt">Timeout</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted">
                            <span class="text-truncate d-block"
                                  title="<?= htmlspecialchars($link->error_message ?? '') ?>">
                                <?= htmlspecialchars($link->error_message ?? '---') ?>
                            </span>
                        </td>
                        <td class="small text-muted">
                            <?= $link->last_checked_at
                                ? date('M j, g:ia', strtotime($link->last_checked_at))
                                : '---' ?>
                        </td>
                        <td class="text-end">
                            <form method="POST"
                                  action="<?= $adminBase ?>/<?= (int) $link->id ?>/recheck"
                                  class="d-inline">
                                <input type="hidden" name="_csrf_token"
                                       value="<?= csrf_token() ?>">
                                <button type="submit"
                                        class="btn btn-xs btn-outline-primary"
                                        title="Recheck">
                                    <i class="ti ti-refresh"></i>
                                </button>
                            </form>
                            <?php if (!$link->dismissed): ?>
                            <form method="POST"
                                  action="<?= $adminBase ?>/<?= (int) $link->id ?>/dismiss"
                                  class="d-inline ms-1">
                                <input type="hidden" name="_csrf_token"
                                       value="<?= csrf_token() ?>">
                                <button type="submit"
                                        class="btn btn-xs btn-outline-secondary"
                                        title="Dismiss permanently">
                                    <i class="ti ti-eye-off"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
