<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Users</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <img src="https://www.gravatar.com/avatar/<?= md5(strtolower($user->email)) ?>?s=32&d=mp"
                                 class="rounded-circle mr-2" width="32" height="32" alt="">
                            <?= esc($user->username ?? $user->email) ?>
                        </td>
                        <td><?= esc($user->email) ?></td>
                        <td>
                            <span class="badge badge-<?= $user->role === 'superadmin' ? 'danger' : ($user->role === 'admin' ? 'warning' : 'secondary') ?>">
                                <?= esc(ucfirst($user->role ?? 'subscriber')) ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= date('M j, Y', strtotime($user->created_at)) ?></td>
                        <td>
                            <?php if ($user->active ?? true): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user->id !== auth()->id()): ?>
                            <a href="<?= base_url('admin/users/' . $user->id . '/edit') ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST" action="<?= base_url('admin/users/' . $user->id . '/delete') ?>" class="d-inline"
                                  onsubmit="return confirm('Delete this user permanently?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                            <?php else: ?>
                            <span class="text-muted small">(you)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No users found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php if (isset($pager)): ?><?= $pager->links() ?><?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
