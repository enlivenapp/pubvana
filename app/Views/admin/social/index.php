<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Social Links</h1>
</div>

<div class="row">
    <!-- Add new link -->
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Add Social Link</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('admin/social/store') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label>Platform</label>
                        <input type="text" name="platform" class="form-control" required placeholder="e.g. Twitter">
                    </div>
                    <div class="form-group">
                        <label>URL</label>
                        <input type="url" name="url" class="form-control" required placeholder="https://twitter.com/yourhandle">
                    </div>
                    <div class="form-group">
                        <label>Font Awesome Icon Class</label>
                        <input type="text" name="icon" class="form-control" placeholder="fab fa-twitter">
                        <small class="form-text text-muted">Use FA5 class e.g. <code>fab fa-facebook</code></small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Add Link</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Current links -->
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Current Links</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:20%">Platform</th>
                            <th>URL</th>
                            <th style="width:18%">Icon Class</th>
                            <th style="width:10%">Status</th>
                            <th style="width:10%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($links as $link): ?>
                        <tr>
                            <td>
                                <i class="<?= esc($link->icon) ?> fa-fw fa-lg me-2 text-primary"></i><?= esc($link->platform) ?>
                            </td>
                            <td>
                                <a href="<?= esc($link->url) ?>" target="_blank" rel="noopener"
                                   class="text-truncate d-inline-block" style="max-width:220px">
                                    <?= esc($link->url) ?>
                                </a>
                            </td>
                            <td><small class="text-muted"><?= esc($link->icon) ?></small></td>
                            <td>
                                <form method="POST" action="<?= base_url('admin/social/' . $link->id . '/toggle') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs <?= $link->is_active ? 'btn-success' : 'btn-outline-secondary' ?>">
                                        <?= $link->is_active ? 'Active' : 'Inactive' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="<?= base_url('admin/social/' . $link->id . '/delete') ?>" class="d-inline" onsubmit="return confirm('Delete this link?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($links)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No social links added yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
