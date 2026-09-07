<?php
/**
 * Edit user - admin form.
 *
 * @var string $pageTitle
 * @var \Enlivenapp\FlightShield\Models\User $editUser
 * @var \Enlivenapp\FlightShield\Models\AuthGroup[] $groups
 * @var string[] $userGroups
 * @var string[] $userPermissions
 * @var bool   $banned        Whether the user is banned
 * @var string|null $banMessage  Admin-set ban reason
 * @var bool   $requiresReset Whether a forced password reset is pending
 */
?>

<div class="d-flex align-items-center justify-content-end mb-4">
    <a href="/admin/users" class="btn btn-outline-secondary">Back</a>
</div>

<form method="POST" action="/admin/users/<?= (int) $editUser->id ?>/update">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Account</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" name="username" id="username" class="form-control"
                               value="<?= htmlspecialchars($editUser->username ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" name="email" id="email" class="form-control"
                               value="<?= htmlspecialchars($email ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password <small class="text-secondary">(leave blank to keep current)</small></label>
                        <input type="password" name="password" id="password" class="form-control">
                    </div>
                </div>
            </div>

            <?php if (!empty($userPermissions)): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Direct Permissions</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($userPermissions as $perm): ?>
                            <span class="badge bg-blue-lt"><?= htmlspecialchars($perm) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Groups</h3>
                </div>
                <div class="card-body">
                    <?php foreach ($groups as $g): ?>
                        <label class="form-check mb-2">
                            <input type="checkbox" name="groups[]" value="<?= htmlspecialchars($g->alias) ?>"
                                   class="form-check-input"
                                   <?= in_array($g->alias, $userGroups, true) ? 'checked' : '' ?>>
                            <span class="form-check-label"><?= htmlspecialchars($g->title) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Status</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span>Active</span>
                        <button type="submit"
                                formaction="/admin/users/<?= (int) $editUser->id ?>/toggle"
                                class="btn btn-sm btn-outline-<?= (int) $editUser->active ? 'warning' : 'success' ?>">
                            <?= (int) $editUser->active ? 'Deactivate' : 'Activate' ?>
                        </button>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span>
                            Banned
                            <?php if (!empty($banned)): ?>
                                <span class="badge bg-danger-lt ms-1">Banned</span>
                            <?php endif; ?>
                        </span>
                        <?php if (!empty($banned)): ?>
                            <button type="submit"
                                    formaction="/admin/users/<?= (int) $editUser->id ?>/unban"
                                    class="btn btn-sm btn-outline-success">Lift Ban</button>
                        <?php else: ?>
                            <button type="submit"
                                    formaction="/admin/users/<?= (int) $editUser->id ?>/ban"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Ban this user? They will not be able to log in.')">Ban</button>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="ban_message">Ban message <small class="text-secondary">(shown to admins, optional)</small></label>
                        <input type="text" name="ban_message" id="ban_message" class="form-control form-control-sm"
                               value="<?= htmlspecialchars((string) ($banMessage ?? '')) ?>"
                               placeholder="Reason for the ban">
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span>
                            Password reset required
                            <?php if (!empty($requiresReset)): ?>
                                <span class="badge bg-warning-lt ms-1">Pending</span>
                            <?php endif; ?>
                        </span>
                        <button type="submit"
                                formaction="/admin/users/<?= (int) $editUser->id ?>/force-reset"
                                class="btn btn-sm btn-outline-<?= !empty($requiresReset) ? 'success' : 'warning' ?>">
                            <?= !empty($requiresReset) ? 'Clear' : 'Force Reset' ?>
                        </button>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span>Profile</span>
                        <a href="/admin/profile/<?= (int) $editUser->id ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                    </div>
                    <div class="text-secondary small mt-3">
                        Last active: <?= htmlspecialchars($editUser->last_active ?? 'Never') ?>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Update User</button>
        </div>
    </div>
</form>
