<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Redirects</h1>
</div>

<div class="row">
    <!-- Add redirect -->
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Add Redirect</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('admin/redirects') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label>From URL <small class="text-muted">(relative, e.g. /old-page)</small></label>
                        <input type="text" name="from_url" class="form-control" required placeholder="/old-page">
                    </div>
                    <div class="form-group">
                        <label>To URL</label>
                        <input type="text" name="to_url" class="form-control" required placeholder="/new-page">
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="type" class="form-control">
                            <option value="301">301 Permanent</option>
                            <option value="302">302 Temporary</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Add Redirect</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Existing redirects -->
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Active Redirects</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="bg-light"><tr><th>From</th><th>To</th><th>Type</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($redirects as $r): ?>
                        <tr>
                            <td><code><?= esc($r->from_url) ?></code></td>
                            <td><code><?= esc($r->to_url) ?></code></td>
                            <td><span class="badge badge-<?= $r->type == 301 ? 'warning' : 'info' ?>"><?= $r->type ?></span></td>
                            <td>
                                <form method="POST" action="<?= base_url('admin/redirects/' . $r->id . '/delete') ?>" class="d-inline" onsubmit="return confirm('Delete redirect?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($redirects)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No redirects configured.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
