<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Pubvana Store</h1>
    <a href="<?= base_url('admin/marketplace') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left fa-sm"></i> Back to Marketplace
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-body text-center py-5">
        <i class="fas fa-store fa-4x text-primary mb-4"></i>
        <h3>Visit the Pubvana Store</h3>
        <p class="text-muted lead mb-4">Browse premium themes and widgets to power up your Pubvana site.<br>Purchase items on the Pubvana store, then install them here using your license key.</p>
        <a href="<?= esc(setting('Marketplace.storeUrl') ?: 'https://pubvana.net/store') ?>" target="_blank" class="btn btn-primary btn-lg">
            <i class="fas fa-external-link-alt mr-2"></i> Open Pubvana Store
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card border-left-primary shadow h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-palette fa-2x text-primary mr-3"></i>
                    <h5 class="mb-0">Premium Themes</h5>
                </div>
                <p class="text-muted">Professional, fully customizable themes for your blog and portfolio.</p>
                <a href="<?= esc(setting('Marketplace.storeUrl') ?: 'https://pubvana.net/store') ?>/themes" target="_blank" class="btn btn-outline-primary btn-sm">Browse Themes →</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card border-left-success shadow h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-puzzle-piece fa-2x text-success mr-3"></i>
                    <h5 class="mb-0">Premium Widgets</h5>
                </div>
                <p class="text-muted">Extend your sidebar and layout with powerful ready-to-use widgets.</p>
                <a href="<?= esc(setting('Marketplace.storeUrl') ?: 'https://pubvana.net/store') ?>/widgets" target="_blank" class="btn btn-outline-success btn-sm">Browse Widgets →</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card border-left-info shadow h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-life-ring fa-2x text-info mr-3"></i>
                    <h5 class="mb-0">Support</h5>
                </div>
                <p class="text-muted">Questions about a purchase or license? Our support team is here to help.</p>
                <a href="<?= esc(setting('Marketplace.storeUrl') ?: 'https://pubvana.net') ?>/support" target="_blank" class="btn btn-outline-info btn-sm">Get Support →</a>
            </div>
        </div>
    </div>
</div>

<div class="card shadow">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Install a Purchased Item</h6>
    </div>
    <div class="card-body">
        <p class="text-muted">After purchasing from the store, enter your download URL and license key to install.</p>
        <form method="POST" action="<?= base_url('admin/marketplace/install') ?>">
            <?= csrf_field() ?>
            <div class="form-row">
                <div class="form-group col-md-5">
                    <label>Download URL</label>
                    <input type="url" name="download_url" class="form-control" placeholder="https://pubvana.net/downloads/...">
                </div>
                <div class="form-group col-md-3">
                    <label>Item Type</label>
                    <select name="item_type" class="form-control">
                        <option value="theme">Theme</option>
                        <option value="widget">Widget</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>License Key</label>
                    <input type="text" name="license_key" class="form-control" placeholder="xxxx-xxxx-xxxx-xxxx">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Install Item</button>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
