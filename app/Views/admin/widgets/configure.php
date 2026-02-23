<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Configure Widget</h1>
    <a href="<?= base_url('admin/widgets') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left fa-sm"></i> Back to Widgets
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><?= esc($widget->name ?? 'Widget') ?></h6>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('admin/widgets/' . $instance->id . '/configure') ?>">
            <?= csrf_field() ?>
            <?= $form_html ?>
            <hr>
            <div class="text-right">
                <a href="<?= base_url('admin/widgets') ?>" class="btn btn-secondary mr-2">Cancel</a>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
