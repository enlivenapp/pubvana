<?php $layout = 'admin/layouts/main'; ob_start();
$isEdit = isset($link) && $link !== null;
$title  = $isEdit ? 'Edit Affiliate Link' : 'New Affiliate Link';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= $title ?></h1>
    <a href="<?= base_url('admin/affiliates') ?>" class="btn btn-secondary btn-sm shadow-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back to Links
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><?= $title ?></h6>
    </div>
    <div class="card-body">
        <form method="POST"
              action="<?= $isEdit ? base_url('admin/affiliates/' . $link->id . '/edit') : base_url('admin/affiliates/create') ?>">
            <?= csrf_field() ?>

            <div class="form-group row">
                <label class="col-sm-3 col-form-label font-weight-bold">
                    Name <span class="text-danger">*</span>
                </label>
                <div class="col-sm-7">
                    <input type="text" name="name" class="form-control"
                           placeholder="e.g. Amazon Hosting"
                           value="<?= esc(old('name', $isEdit ? $link->name : '')) ?>">
                    <small class="text-muted">Internal label — not shown to visitors.</small>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-3 col-form-label font-weight-bold">
                    Slug <span class="text-danger">*</span>
                </label>
                <div class="col-sm-7">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text text-muted"><?= base_url('go/') ?></span>
                        </div>
                        <input type="text" name="slug" id="slug" class="form-control text-monospace"
                               placeholder="my-link"
                               value="<?= esc(old('slug', $isEdit ? $link->slug : '')) ?>">
                    </div>
                    <small class="text-muted">Letters, numbers, hyphens and underscores only. Cannot be changed once links are shared.</small>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-3 col-form-label font-weight-bold">
                    Destination URL <span class="text-danger">*</span>
                </label>
                <div class="col-sm-7">
                    <input type="url" name="destination_url" class="form-control"
                           placeholder="https://example.com/product?ref=pubvana"
                           value="<?= esc(old('destination_url', $isEdit ? $link->destination_url : '')) ?>">
                    <small class="text-muted">Visitors will be 301-redirected here.</small>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-3 col-form-label font-weight-bold">Status</label>
                <div class="col-sm-7">
                    <div class="custom-control custom-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" class="custom-control-input" id="is_active"
                               name="is_active" value="1"
                               <?= old('is_active', $isEdit ? $link->is_active : 1) ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="is_active">Active</label>
                    </div>
                    <small class="text-muted">Inactive links return a 404.</small>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-sm-7 offset-sm-3">
                    <button type="submit" class="btn btn-primary">
                        <?= $isEdit ? 'Save Changes' : 'Create Link' ?>
                    </button>
                    <a href="<?= base_url('admin/affiliates') ?>" class="btn btn-secondary ml-2">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php $extra_scripts = <<<'SCRIPT'
<script>
// Auto-generate slug from name when creating a new link
(function () {
    var nameInput = document.querySelector('[name="name"]');
    var slugInput = document.getElementById('slug');
    if (!nameInput || !slugInput || slugInput.value !== '') return; // skip on edit
    nameInput.addEventListener('input', function () {
        slugInput.value = nameInput.value
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    });
})();
</script>
SCRIPT;
?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
