<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Theme Options — <?= esc($theme->name) ?></h1>
    <a href="<?= base_url('admin/themes') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left fa-sm"></i> Back to Themes
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Customize Theme</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('admin/themes/' . $theme->id . '/options') ?>">
            <?= csrf_field() ?>
            <?php foreach ($options as $key => $opt): ?>
                <?php
                $savedVal = $saved[$key] ?? ($opt['default'] ?? '');
                $label    = esc($opt['label']   ?? $key);
                $type     = $opt['type']    ?? 'text';
                ?>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label font-weight-bold"><?= $label ?></label>
                    <div class="col-sm-9">
                        <?php if ($type === 'checkbox'): ?>
                            <div class="custom-control custom-switch">
                                <input type="hidden" name="options[<?= esc($key) ?>]" value="0">
                                <input type="checkbox" class="custom-control-input" id="opt_<?= esc($key) ?>"
                                       name="options[<?= esc($key) ?>]" value="1"
                                       <?= $savedVal ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="opt_<?= esc($key) ?>">Enable</label>
                            </div>
                        <?php elseif ($type === 'textarea'): ?>
                            <textarea class="form-control" name="options[<?= esc($key) ?>]" rows="4"><?= esc($savedVal) ?></textarea>
                        <?php elseif ($type === 'select'): ?>
                            <select class="form-control" name="options[<?= esc($key) ?>]">
                                <?php foreach (($opt['choices'] ?? []) as $val => $lbl): ?>
                                    <option value="<?= esc($val) ?>" <?= $savedVal == $val ? 'selected' : '' ?>><?= esc($lbl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($type === 'color'): ?>
                            <input type="color" class="form-control" name="options[<?= esc($key) ?>]" value="<?= esc($savedVal) ?>">
                        <?php elseif ($type === 'number'): ?>
                            <input type="number" class="form-control" name="options[<?= esc($key) ?>]"
                                   value="<?= esc($savedVal) ?>"
                                   min="<?= esc($opt['min'] ?? 0) ?>" max="<?= esc($opt['max'] ?? 9999) ?>">
                        <?php else: ?>
                            <input type="text" class="form-control" name="options[<?= esc($key) ?>]" value="<?= esc($savedVal) ?>">
                        <?php endif; ?>
                        <?php if (!empty($opt['help'])): ?>
                            <small class="form-text text-muted"><?= esc($opt['help']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($options)): ?>
                <p class="text-muted">This theme has no configurable options.</p>
            <?php else: ?>
                <hr>
                <div class="text-right">
                    <button type="submit" class="btn btn-primary">Save Options</button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
