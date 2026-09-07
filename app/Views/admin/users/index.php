<?php
/**
 * User listing - admin page.
 *
 * @var string $pageTitle
 * @var \Enlivenapp\FlightShield\Models\User[] $users
 * @var int $total
 * @var int $page
 * @var int $perPage
 */
?>

<div class="d-flex align-items-center justify-content-end gap-2 mb-4">
    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#inviteUserModal">
        <i class="ti ti-mail me-1"></i> Invite User
    </button>
    <a href="/admin/users/create" class="btn btn-primary">
        <i class="ti ti-plus me-1"></i> New User
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Status</th>
                    <th>Groups</th>
                    <th>Last Active</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">No users found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <a href="/admin/users/<?= (int) $u->id ?>/edit">
                                    <?= htmlspecialchars($u->username ?? 'unnamed') ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($u->isBanned()): ?>
                                    <span class="badge bg-danger-lt">Banned</span>
                                <?php elseif ((int) $u->active): ?>
                                    <span class="badge bg-success-lt">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-lt">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $groups = $u->getGroups(); ?>
                                <?php if (!empty($groups)): ?>
                                    <?php foreach ($groups as $g): ?>
                                        <span class="badge bg-blue-lt"><?= htmlspecialchars($g) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-secondary">None</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-secondary">
                                <?= htmlspecialchars($u->last_active ?? 'Never') ?>
                            </td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <a href="/admin/users/<?= (int) $u->id ?>/edit" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="POST" action="/admin/users/<?= (int) $u->id ?>/toggle" class="d-inline">
                                        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                        <button class="btn btn-sm btn-outline-<?= (int) $u->active ? 'warning' : 'success' ?>">
                                            <?= (int) $u->active ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                    <?php if ($u->isBanned()): ?>
                                        <form method="POST" action="/admin/users/<?= (int) $u->id ?>/unban" class="d-inline">
                                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                            <button class="btn btn-sm btn-outline-success">Unban</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="/admin/users/<?= (int) $u->id ?>/ban" class="d-inline"
                                              onsubmit="return confirm('Ban this user? They will not be able to log in.')">
                                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                            <button class="btn btn-sm btn-outline-danger">Ban</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" action="/admin/users/<?= (int) $u->id ?>/delete"
                                          class="d-inline" onsubmit="return confirm('Delete this user?')">
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

<?php $totalPages = (int) ceil($total / $perPage); ?>
<?php if ($totalPages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="/admin/users?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<div class="modal fade" id="inviteUserModal" tabindex="-1" role="dialog" aria-labelledby="inviteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" action="/admin/users/invite">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="inviteUserModalLabel">Invite User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-secondary mb-3">Enter an email address to send an invitation pointing to the registration page.</p>
                    <div class="mb-3">
                        <label class="form-label" for="invite-email">Email</label>
                        <input type="email" name="email" id="invite-email" class="form-control" required autofocus>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary ms-auto">Send Invite</button>
                </div>
            </div>
        </form>
    </div>
</div>
