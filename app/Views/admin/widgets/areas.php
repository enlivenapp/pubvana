<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Widget Areas</h1>
</div>

<?php if (empty($areas)): ?>
    <div class="alert alert-info">No widget areas found. Activate a theme to enable widget areas.</div>
<?php else: ?>

<ul class="nav nav-tabs mb-3" id="areaTabs" role="tablist">
    <?php foreach ($areas as $i => $area): ?>
    <li class="nav-item">
        <a class="nav-link <?= $i === 0 ? 'active' : '' ?>"
           data-toggle="tab" href="#area-<?= $area->id ?>"><?= esc($area->name) ?></a>
    </li>
    <?php endforeach; ?>
</ul>

<div class="tab-content">
<?php foreach ($areas as $i => $area): ?>
<div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="area-<?= $area->id ?>">
    <div class="row">
        <!-- Active widgets in this area -->
        <div class="col-md-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary"><?= esc($area->name) ?></h6>
                    <small class="text-muted">Drag to reorder</small>
                </div>
                <div class="card-body p-2">
                    <ul class="list-group widget-sortable" id="sortable-<?= $area->id ?>" data-area="<?= $area->id ?>">
                        <?php $areaInstances = array_filter($instances, fn($wi) => $wi->widget_area_id == $area->id); ?>
                        <?php usort($areaInstances, fn($a, $b) => $a->sort_order - $b->sort_order); ?>
                        <?php if (empty($areaInstances)): ?>
                        <li class="list-group-item text-center text-muted py-4" id="empty-<?= $area->id ?>">
                            No widgets in this area. Add one from the list →
                        </li>
                        <?php endif; ?>
                        <?php foreach ($areaInstances as $wi): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center" data-id="<?= $wi->id ?>">
                            <span>
                                <i class="fas fa-grip-vertical text-muted mr-2" style="cursor:grab"></i>
                                <strong><?= esc($wi->widget_name ?? $wi->folder) ?></strong>
                                <?php
                                $opts = json_decode($wi->options_json ?? '{}', true);
                                if (!empty($opts['title'])): ?>
                                    <small class="text-muted ml-2"><?= esc($opts['title']) ?></small>
                                <?php endif; ?>
                            </span>
                            <span>
                                <a href="<?= base_url('admin/widgets/' . $wi->id . '/configure') ?>" class="btn btn-xs btn-outline-primary">Configure</a>
                                <form method="POST" action="<?= base_url('admin/widgets/' . $wi->id . '/remove') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs btn-outline-danger" onclick="return confirm('Remove widget?')">Remove</button>
                                </form>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Available widgets to add -->
        <div class="col-md-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Available Widgets</h6>
                </div>
                <div class="card-body p-2">
                    <?php foreach ($available as $w):
                        $wInfoFile = WIDGETS_PATH . $w->folder . '/widget_info.php';
                        $wInfo = is_file($wInfoFile) ? require $wInfoFile : [];
                    ?>
                    <form method="POST" action="<?= base_url('admin/widgets/add') ?>" class="mb-1">
                        <?= csrf_field() ?>
                        <input type="hidden" name="widget_id" value="<?= $w->id ?>">
                        <input type="hidden" name="widget_area_id" value="<?= $area->id ?>">
                        <div class="d-flex justify-content-between align-items-center border rounded p-2">
                            <div>
                                <strong><?= esc($w->name) ?></strong>
                                <?php if (!empty($wInfo['premium'])): ?>
                                    <span class="badge badge-warning text-dark" style="font-size:0.6rem">Premium</span>
                                <?php endif; ?><br>
                                <small class="text-muted"><?= esc($w->description) ?></small>
                            </div>
                            <button class="btn btn-sm btn-outline-primary ml-2">Add</button>
                        </div>
                    </form>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php $extra_scripts = <<<'SCRIPT'
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.querySelectorAll('.widget-sortable').forEach(function(el) {
    Sortable.create(el, {
        animation: 150,
        handle: '.fa-grip-vertical',
        ghostClass: 'bg-light',
        onEnd: function(evt) {
            var areaId = el.dataset.area;
            var ids = Array.from(el.querySelectorAll('[data-id]')).map(li => li.dataset.id);
            fetch(baseUrl + 'admin/widgets/reorder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : ''
                },
                body: JSON.stringify({ area_id: areaId, order: ids })
            });
        }
    });
});
var baseUrl = '<?= base_url() ?>';
</script>
SCRIPT;
?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
