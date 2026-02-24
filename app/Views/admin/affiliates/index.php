<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Affiliate Links</h1>
    <a href="<?= base_url('admin/affiliates/create') ?>" class="btn btn-primary btn-sm shadow-sm">
        <i class="fas fa-plus fa-sm"></i> New Link
    </a>
</div>

<?php if (empty($links)): ?>
<div class="card shadow mb-4">
    <div class="card-body text-center text-muted py-5">
        <i class="fas fa-link fa-3x mb-3 d-block"></i>
        No affiliate links yet. Create one to get started.
    </div>
</div>
<?php else: ?>
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><?= count($links) ?> Link<?= count($links) !== 1 ? 's' : '' ?></h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Name</th>
                        <th>Short URL</th>
                        <th>Destination</th>
                        <th class="text-center">Clicks</th>
                        <th class="text-center">Active</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($links as $link): ?>
                    <tr>
                        <td class="font-weight-bold"><?= esc($link->name) ?></td>
                        <td>
                            <a href="<?= base_url('go/' . esc($link->slug)) ?>"
                               target="_blank" class="text-monospace">
                                /go/<?= esc($link->slug) ?>
                            </a>
                        </td>
                        <td class="text-truncate" style="max-width:260px">
                            <span class="text-muted small" title="<?= esc($link->destination_url) ?>">
                                <?= esc($link->destination_url) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="<?= base_url('admin/affiliates/' . $link->id . '/clicks') ?>"
                               class="badge badge-<?= $link->click_count > 0 ? 'primary' : 'secondary' ?> px-2 py-1">
                                <?= (int) $link->click_count ?>
                            </a>
                        </td>
                        <td class="text-center">
                            <?php if ($link->is_active): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <a href="<?= base_url('admin/affiliates/' . $link->id . '/edit') ?>"
                               class="btn btn-sm btn-outline-primary mr-1">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST"
                                  action="<?= base_url('admin/affiliates/' . $link->id . '/delete') ?>"
                                  class="d-inline"
                                  onsubmit="return confirm('Delete this affiliate link and all its click data?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
