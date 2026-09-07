<?php
/**
 * Incoming 404 manager.
 *
 * @var string $pageTitle
 * @var string $status
 * @var \Pubvana\Plugins\Redirects\Models\RedirectLink[] $entries
 * @var string $adminBase
 */
?>

<ul class="nav nav-tabs mb-3">
    <?php foreach (['active' => 'Active', 'resolved' => 'Resolved', 'ignored' => 'Ignored', 'all' => 'All'] as $key => $label): ?>
        <li class="nav-item">
            <a class="nav-link <?= $status === $key ? 'active' : '' ?>" href="<?= $adminBase ?>/404-manager?status=<?= urlencode($key) ?>">
                <?= htmlspecialchars($label) ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Path</th>
                    <th>Status</th>
                    <th>Hits</th>
                    <th>Last Seen</th>
                    <th>Referrer</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($entries)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">No redirect links found for this filter.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td>
                                <code><?= htmlspecialchars($entry->source_path) ?></code>
                                <?php if (!empty($entry->last_query_string)): ?>
                                    <div class="small text-secondary mt-1">Last query: ?<?= htmlspecialchars((string) $entry->last_query_string) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($entry->last_user_agent)): ?>
                                    <div class="small text-secondary text-truncate mt-1" style="max-width: 28rem;">
                                        UA: <?= htmlspecialchars((string) $entry->last_user_agent) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int) $entry->resolved_redirect_id > 0): ?>
                                    <span class="badge bg-success-lt">Resolved</span>
                                <?php elseif ((int) $entry->ignored === 1): ?>
                                    <span class="badge bg-secondary-lt">Ignored</span>
                                <?php else: ?>
                                    <span class="badge bg-red-lt">Open</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int) $entry->hit_count ?></td>
                            <td>
                                <?= htmlspecialchars((string) ($entry->last_seen_at ?? '')) ?>
                                <?php if (!empty($entry->first_seen_at)): ?>
                                    <div class="small text-secondary">First: <?= htmlspecialchars((string) $entry->first_seen_at) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-break">
                                <?= !empty($entry->last_referrer) ? htmlspecialchars((string) $entry->last_referrer) : '<span class="text-secondary">—</span>' ?>
                            </td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <a href="<?= $adminBase ?>/create?source_path=<?= urlencode($entry->source_path) ?>&incoming_404_id=<?= (int) $entry->id ?>"
                                       class="btn btn-sm btn-outline-primary">Create Redirect</a>
                                    <?php if ((int) $entry->ignored === 1): ?>
                                        <form method="POST" action="<?= $adminBase ?>/404-manager/<?= (int) $entry->id ?>/unignore" class="d-inline">
                                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                            <button class="btn btn-sm btn-outline-secondary">Unignore</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="<?= $adminBase ?>/404-manager/<?= (int) $entry->id ?>/ignore" class="d-inline">
                                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                            <button class="btn btn-sm btn-outline-secondary">Ignore</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" action="<?= $adminBase ?>/404-manager/<?= (int) $entry->id ?>/delete"
                                          class="d-inline" onsubmit="return confirm('Delete this 404 entry?')">
                                        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
