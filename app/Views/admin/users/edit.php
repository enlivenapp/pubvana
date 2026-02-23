<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Edit User</h1>
    <a href="<?= base_url('admin/users') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left fa-sm"></i> Back to Users
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <form method="POST" action="<?= base_url('admin/users/' . $editUser->id . '/edit') ?>">
            <?= csrf_field() ?>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" value="<?= esc($editUser->username ?? '') ?>">
                </div>
                <div class="form-group col-md-6">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required value="<?= esc($editUser->email) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Role</label>
                    <select name="role" class="form-control">
                        <?php foreach (['subscriber', 'author', 'editor', 'admin', 'superadmin'] as $r): ?>
                        <option value="<?= $r ?>" <?= ($editUser->role ?? '') === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>New Password <small class="text-muted">(leave blank to keep current)</small></label>
                    <input type="password" name="password" class="form-control" autocomplete="new-password">
                </div>
            </div>
            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" class="custom-control-input" id="active" name="active" value="1"
                           <?= ($editUser->active ?? true) ? 'checked' : '' ?>>
                    <label class="custom-control-label" for="active">Account active</label>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-end">
                <a href="<?= base_url('admin/users') ?>" class="btn btn-secondary mr-2">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
