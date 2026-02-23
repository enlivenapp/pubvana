<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Pubvana Store</h1>
    <div>
        <a href="<?= esc($storeUrl) ?>" target="_blank" class="btn btn-sm btn-outline-primary mr-2">
            <i class="fas fa-external-link-alt fa-sm mr-1"></i> Browse Full Store
        </a>
        <a href="<?= base_url('admin/marketplace') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left fa-sm"></i> Back to Marketplace
        </a>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<?php if (empty($items)): ?>
    <div class="card shadow mb-4">
        <div class="card-body text-center py-5">
            <i class="fas fa-store fa-4x text-muted mb-4"></i>
            <h4 class="text-muted">No products available</h4>
            <p class="text-muted">Could not load products from the store. Please check back later.</p>
            <a href="<?= esc($storeUrl) ?>" target="_blank" class="btn btn-primary">
                <i class="fas fa-external-link-alt mr-1"></i> Visit Pubvana Store
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($items as $item): ?>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow h-100">
                <?php if (! empty($item->screenshot_url)): ?>
                    <img src="<?= esc($item->screenshot_url) ?>" class="card-img-top" alt="<?= esc($item->name) ?>" style="height:180px;object-fit:cover;">
                <?php else: ?>
                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:180px;">
                        <i class="fas fa-<?= $item->type === 'theme' ? 'palette' : 'puzzle-piece' ?> fa-4x text-muted"></i>
                    </div>
                <?php endif; ?>
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-start justify-content-between mb-1">
                        <h5 class="mb-0"><?= esc($item->name) ?></h5>
                        <?php if ($item->is_free): ?>
                            <span class="badge badge-success ml-2">Free</span>
                        <?php else: ?>
                            <span class="badge badge-primary ml-2">$<?= esc(number_format((float)($item->price ?? 0), 2)) ?></span>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted mb-2">
                        v<?= esc($item->version ?? '1.0') ?>
                        <?php if (! empty($item->author)): ?> &bull; <?= esc($item->author) ?><?php endif; ?>
                        &bull; <?= ucfirst(esc($item->type)) ?>
                    </small>
                    <p class="text-muted small flex-grow-1"><?= esc($item->description ?? '') ?></p>

                    <form method="POST" action="<?= base_url('admin/store/install') ?>" class="mt-auto">
                        <?= csrf_field() ?>
                        <input type="hidden" name="slug" value="<?= esc($item->slug) ?>">
                        <input type="hidden" name="item_type" value="<?= esc($item->type) ?>">
                        <?php if ($item->is_free): ?>
                            <input type="hidden" name="license_key" value="">
                            <button type="submit" class="btn btn-success btn-sm btn-block">
                                <i class="fas fa-download mr-1"></i> Install Free
                            </button>
                        <?php else: ?>
                            <div class="input-group mb-2">
                                <input type="text" name="license_key" class="form-control form-control-sm"
                                       placeholder="License key" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm btn-block">
                                <i class="fas fa-download mr-1"></i> Install
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
                <?php if (! empty($item->store_url)): ?>
                <div class="card-footer text-right">
                    <a href="<?= esc($item->store_url) ?>" target="_blank" class="small text-primary">
                        View in store <i class="fas fa-external-link-alt fa-xs"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
