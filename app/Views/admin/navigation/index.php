<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Navigation</h1>
</div>

<ul class="nav nav-tabs mb-3">
    <?php foreach (['primary' => 'Primary Menu', 'footer' => 'Footer Menu'] as $grp => $label): ?>
    <li class="nav-item">
        <a class="nav-link <?= $group === $grp ? 'active' : '' ?>" href="<?= base_url('admin/navigation?group=' . $grp) ?>"><?= $label ?></a>
    </li>
    <?php endforeach; ?>
</ul>

<div class="row">
    <!-- Add new item -->
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Add Menu Item</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('admin/navigation') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="nav_group" value="<?= esc($group) ?>">
                    <div class="form-group">
                        <label>Label</label>
                        <input type="text" name="label" class="form-control" required placeholder="e.g. About Us">
                    </div>
                    <div class="form-group">
                        <label>URL</label>
                        <input type="text" name="url" class="form-control" required placeholder="/about">
                    </div>
                    <div class="form-group">
                        <label>Parent</label>
                        <select name="parent_id" class="form-control">
                            <option value="">— Top level —</option>
                            <?php foreach ($items as $item): ?>
                            <?php if (!$item->parent_id): ?>
                            <option value="<?= $item->id ?>"><?= esc($item->label) ?></option>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Target</label>
                        <select name="target" class="form-control">
                            <option value="_self">Same window</option>
                            <option value="_blank">New window</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Add Item</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Current items -->
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Menu Items</h6>
                <small class="text-muted">Drag to reorder</small>
            </div>
            <div class="card-body p-2">
                <?php if (empty($items)): ?>
                    <p class="text-center text-muted py-3">No items in this menu.</p>
                <?php else: ?>
                <ul class="list-group nav-sortable" id="nav-sortable">
                    <?php foreach ($items as $item): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center" data-id="<?= $item->id ?>">
                        <span>
                            <i class="fas fa-grip-vertical text-muted mr-2" style="cursor:grab"></i>
                            <?= $item->parent_id ? '<span class="ml-3 text-muted">↳ </span>' : '' ?>
                            <strong><?= esc($item->label) ?></strong>
                            <small class="text-muted ml-2"><?= esc($item->url) ?></small>
                            <?php if ($item->target === '_blank'): ?>
                            <i class="fas fa-external-link-alt fa-xs text-muted ml-1"></i>
                            <?php endif; ?>
                        </span>
                        <form method="POST" action="<?= base_url('admin/navigation/' . $item->id . '/delete') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-xs btn-outline-danger" onclick="return confirm('Remove this item?')">Remove</button>
                        </form>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php $extra_scripts = <<<'SCRIPT'
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
var el = document.getElementById('nav-sortable');
if (el) {
    Sortable.create(el, {
        animation: 150,
        handle: '.fa-grip-vertical',
        onEnd: function() {
            var ids = Array.from(el.querySelectorAll('[data-id]')).map(li => li.dataset.id);
            fetch(baseUrl + 'admin/navigation/reorder', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ order: ids })
            });
        }
    });
}
var baseUrl = '<?= base_url() ?>';
</script>
SCRIPT;
?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
