<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Marketplace</h1>
    <div>
        <form method="POST" action="<?= base_url('admin/marketplace/refresh') ?>" class="d-inline">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-secondary mr-2">
                <i class="fas fa-sync-alt fa-sm"></i> Refresh
            </button>
        </form>
        <a href="<?= base_url('admin/store') ?>" class="btn btn-sm btn-outline-info">
            <i class="fas fa-shopping-cart fa-sm"></i> Visit Store
        </a>
    </div>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link <?= $filter === '' ? 'active' : '' ?>" href="<?= base_url('admin/marketplace') ?>">All</a></li>
    <li class="nav-item"><a class="nav-link <?= $filter === 'theme' ? 'active' : '' ?>" href="<?= base_url('admin/marketplace/themes') ?>">Themes</a></li>
    <li class="nav-item"><a class="nav-link <?= $filter === 'widget' ? 'active' : '' ?>" href="<?= base_url('admin/marketplace/widgets') ?>">Widgets</a></li>
</ul>

<?php if (!empty($updates)): ?>
<div class="alert alert-warning d-flex align-items-center">
    <i class="fas fa-exclamation-triangle mr-2"></i>
    <strong><?= count($updates) ?> update(s) available.</strong>
    <?php foreach ($updates as $u): ?>
    <form method="POST" action="<?= base_url('admin/marketplace/update/' . $u->slug) ?>" class="ml-2">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-warning">Update <?= esc($u->name) ?></button>
    </form>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="row">
<?php foreach ($items as $item): ?>
    <div class="col-md-4 mb-4">
        <div class="card shadow h-100 <?= $item->installed_version ? 'border-success' : '' ?>">
            <?php if (!empty($item->screenshot_url)): ?>
                <img src="<?= esc($item->screenshot_url) ?>" class="card-img-top" style="height:160px;object-fit:cover" alt="">
            <?php else: ?>
                <div class="card-img-top bg-gradient-primary d-flex align-items-center justify-content-center" style="height:160px">
                    <i class="fas fa-<?= $item->item_type === 'theme' ? 'palette' : 'puzzle-piece' ?> fa-3x text-white-50"></i>
                </div>
            <?php endif; ?>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <h5 class="card-title mb-1"><?= esc($item->name) ?></h5>
                    <?php if ($item->installed_version): ?>
                        <span class="badge badge-success">Installed</span>
                    <?php endif; ?>
                </div>
                <p class="text-muted small mb-2"><?= esc($item->description) ?></p>
                <p class="text-muted" style="font-size:0.8rem">
                    By <?= esc($item->author ?? 'Unknown') ?> &middot; v<?= esc($item->version) ?>
                    &middot; <span class="badge badge-<?= $item->item_type === 'theme' ? 'primary' : 'secondary' ?>"><?= ucfirst($item->item_type) ?></span>
                </p>
            </div>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <?php if ($item->is_free): ?>
                    <span class="text-success font-weight-bold">Free</span>
                    <?php if (!$item->installed_version): ?>
                    <form method="POST" action="<?= base_url('admin/marketplace/install') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="slug" value="<?= esc($item->slug) ?>">
                        <input type="hidden" name="item_type" value="<?= esc($item->item_type) ?>">
                        <input type="hidden" name="download_url" value="<?= esc($item->download_url) ?>">
                        <button class="btn btn-sm btn-primary">Install</button>
                    </form>
                    <?php else: ?>
                    <span class="text-muted small">v<?= esc($item->installed_version) ?> installed</span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="font-weight-bold text-dark">$<?= number_format($item->price ?? 0, 2) ?></span>
                    <a href="<?= esc($item->store_url) ?>" target="_blank" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-external-link-alt fa-xs"></i> Buy Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php if (empty($items)): ?>
    <div class="col-12"><p class="text-center text-muted py-4">No items found in the marketplace.</p></div>
<?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
