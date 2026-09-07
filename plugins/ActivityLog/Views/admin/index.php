<?php
/**
 * @var string $pageTitle
 * @var array  $logs
 * @var array  $filters
 * @var array  $actions
 * @var array  $entityTypes
 * @var array  $users
 * @var int    $page
 * @var int    $perPage
 * @var int    $total
 * @var int    $totalPages
 * @var string $adminBase
 */
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="h4 mb-0"><?= htmlspecialchars($pageTitle) ?></h2>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3" id="filterForm">
            <div class="col-md-3">
                <label class="form-label">User</label>
                <select name="user_id" class="form-select">
                    <option value="">All Users</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= (int) $user['user_id'] ?>" <?= ($filters['user_id'] ?? '') == $user['user_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['user_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Action</label>
                <select name="action" class="form-select">
                    <option value="">All Actions</option>
                    <?php foreach ($actions as $action): ?>
                        <option value="<?= htmlspecialchars($action) ?>" <?= ($filters['action'] ?? '') === $action ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($action)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Entity Type</label>
                <select name="entity_type" class="form-select">
                    <option value="">All Types</option>
                    <?php foreach ($entityTypes as $type): ?>
                        <option value="<?= htmlspecialchars($type) ?>" <?= ($filters['entity_type'] ?? '') === $type ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $type))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Entity Name</label>
                <input type="text" name="entity_name" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($filters['entity_name'] ?? '') ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 me-2">
                    <i class="ti ti-filter me-1"></i> Filter
                </button>
                <a href="<?= $adminBase ?>" class="btn btn-outline-secondary w-100">
                    <i class="ti ti-x me-1"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Log Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th style="width: 160px;">Date / Time</th>
                    <th style="width: 140px;">User</th>
                    <th style="width: 100px;">Action</th>
                    <th style="width: 140px;">Entity Type</th>
                    <th>Entity</th>
                    <th style="width: 120px;">IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            No activity log entries found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="text-nowrap">
                                <?= date('M j, Y H:i:s', strtotime($log->created_at)) ?>
                            </td>
                            <td>
                                <?php if ($log->user_id): ?>
                                    <strong><?= htmlspecialchars($log->user_name) ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">System</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= actionColor($log->action) ?> text-capitalize">
                                    <?= htmlspecialchars($log->action) ?>
                                </span>
                            </td>
                            <td class="text-capitalize">
                                <?= htmlspecialchars(str_replace('_', ' ', $log->entity_type)) ?>
                            </td>
                            <td>
                                <?php if ($log->entity_id): ?>
                                    <code>#<?= (int) $log->entity_id ?></code>
                                <?php endif; ?>
                                <?php if ($log->entity_name): ?>
                                    <span class="ms-2"><?= htmlspecialchars($log->entity_name) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap text-muted small">
                                <?= htmlspecialchars($log->ip) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer">
            <nav aria-label="Activity log pagination">
                <ul class="pagination pagination-sm mb-0 justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= buildPageUrl($page - 1, $filters, $adminBase) ?>">&laquo; Previous</a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled"><span class="page-link">&laquo; Previous</span></li>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= buildPageUrl($i, $filters, $adminBase) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= buildPageUrl($page + 1, $filters, $adminBase) ?>">Next &raquo;</a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled"><span class="page-link">Next &raquo;</span></li>
                    <?php endif; ?>
                </ul>
                <p class="text-muted small text-center mb-0 mt-2">
                    Showing <?= ($page - 1) * $perPage + 1 ?>&ndash;<?= min($page * $perPage, $total) ?> of <?= $total ?> entries
                </p>
            </nav>
        </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('filterForm').addEventListener('submit', function(e) {
    // Remove empty filter values from URL
    const formData = new FormData(this);
    const params = new URLSearchParams();
    for (const [key, value] of formData.entries()) {
        if (value !== '') {
            params.set(key, value);
        }
    }
    // Reset page to 1 on filter change
    params.delete('page');
    window.location.href = '<?= $adminBase ?>?' + params.toString();
    e.preventDefault();
});
</script>

<?php
// Helper methods for the view
function actionColor(string $action): string
{
    return match ($action) {
        'create', 'publish', 'activate' => 'success',
        'update', 'toggle' => 'info',
        'delete' => 'danger',
        'approve' => 'success',
        'reject' => 'warning',
        'restore' => 'primary',
        'settings_change' => 'secondary',
        default => 'secondary',
    };
}

function buildPageUrl(int $page, array $filters, string $adminBase): string
{
    $params = [];
    foreach ($filters as $key => $value) {
        if ($value !== '' && $value !== null) {
            $params[$key] = $value;
        }
    }
    $params['page'] = $page;
    return $adminBase . '?' . http_build_query($params);
}
?>