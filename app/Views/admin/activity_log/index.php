<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Activity Log</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Admin Actions</h6>
        <form method="GET" action="<?= base_url('admin/activity-log') ?>" class="form-inline">
            <label class="mr-2 text-muted small">Filter:</label>
            <select name="type" class="form-control form-control-sm" onchange="this.form.submit()">
                <option value="" <?= $type === '' ? 'selected' : '' ?>>All actions</option>
                <option value="post"        <?= $type === 'post'        ? 'selected' : '' ?>>Posts</option>
                <option value="page"        <?= $type === 'page'        ? 'selected' : '' ?>>Pages</option>
                <option value="user"        <?= $type === 'user'        ? 'selected' : '' ?>>Users</option>
                <option value="theme"       <?= $type === 'theme'       ? 'selected' : '' ?>>Themes</option>
                <option value="setting"     <?= $type === 'setting'     ? 'selected' : '' ?>>Settings</option>
                <option value="marketplace" <?= $type === 'marketplace' ? 'selected' : '' ?>>Marketplace</option>
            </select>
        </form>
    </div>
    <div class="card-body p-0">
        <?php if (empty($entries)): ?>
            <div class="p-4 text-muted text-center">No activity recorded yet.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width:160px">Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td class="text-muted small" title="<?= esc($entry->created_at) ?>">
                            <?= esc($entry->created_at) ?>
                        </td>
                        <td>
                            <?php if ($entry->user_id): ?>
                                <a href="<?= base_url('admin/users/' . $entry->user_id . '/edit') ?>">
                                    <?= esc($entry->username) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted"><?= esc($entry->username ?: '—') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code class="small"><?= esc($entry->action) ?></code>
                        </td>
                        <td><?= esc($entry->description) ?></td>
                        <td class="text-muted small"><?= esc($entry->ip_address ?? '—') ?></td>
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
