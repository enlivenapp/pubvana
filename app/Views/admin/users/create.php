<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Create User</h1>
    <a href="<?= base_url('admin/users') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left fa-sm"></i> Back to Users
    </a>
</div>

<?php if (session('errors')): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach (session('errors') as $e): ?>
        <li><?= esc($e) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-body">
        <form method="POST" action="<?= base_url('admin/users/create') ?>">
            <?= csrf_field() ?>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required
                           value="<?= esc(old('username')) ?>" minlength="3" maxlength="30">
                </div>
                <div class="form-group col-md-6">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required
                           value="<?= esc(old('email')) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required
                           autocomplete="new-password" minlength="8">
                </div>
                <div class="form-group col-md-6">
                    <label>Role</label>
                    <select name="role" class="form-control">
                        <?php foreach (['subscriber', 'author', 'editor', 'admin', 'superadmin'] as $r): ?>
                        <option value="<?= $r ?>" <?= old('role') === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-end">
                <a href="<?= base_url('admin/users') ?>" class="btn btn-secondary mr-2">Cancel</a>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
