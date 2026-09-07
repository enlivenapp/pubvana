<?php
/**
 * Captcha settings page (Settings > Captcha) - standalone.
 *
 * Provider configuration (declared Captcha.* keys) plus one switch per
 * registered captcha area. Switches only take effect once a provider and
 * site key are saved.
 *
 * @var string $pageTitle
 * @var array<int, array<string, mixed>> $fields Declared fields with resolved values
 * @var array<string, array{label: string, description: string, enabled: bool}> $areas
 * @var bool $enabled Whether a usable provider is configured
 * @var string|null $flash One-shot save message
 */
?>

<?php if ($flash !== null && $flash !== ''): ?>
    <div class="alert alert-success alert-dismissible" role="alert">
        <div class="d-flex">
            <div>
                <i class="ti ti-circle-check icon alert-icon"></i>
            </div>
            <div><?= htmlspecialchars($flash) ?></div>
        </div>
        <a class="btn-close" data-bs-dismiss="alert" aria-label="Close"></a>
    </div>
<?php endif; ?>

<form method="POST" action="/admin/captcha/save" autocomplete="off">
    <?= csrf_field() ?>

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Provider</h3>
        </div>
        <div class="card-body">
            <?php if (empty($fields)): ?>
                <div class="text-secondary">No captcha settings declared.</div>
            <?php endif; ?>
            <div class="row g-3">
                <?php foreach ($fields as $field): ?>
                    <?php $key = (string) $field['key']; ?>
                    <div class="col-md-4">
                        <?php if (($field['type'] ?? '') === 'select'): ?>
                            <label class="form-label" for="setting-<?= htmlspecialchars($key) ?>">
                                <?= htmlspecialchars((string) $field['label']) ?>
                            </label>
                            <select class="form-select"
                                    id="setting-<?= htmlspecialchars($key) ?>"
                                    name="settings[<?= htmlspecialchars($key) ?>]">
                                <?php foreach ($field['options'] as $optionValue => $optionLabel): ?>
                                    <option value="<?= htmlspecialchars((string) $optionValue) ?>"
                                        <?= (string) $field['value'] === (string) $optionValue ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) $optionLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif (($field['type'] ?? '') === 'password'): ?>
                            <label class="form-label" for="setting-<?= htmlspecialchars($key) ?>">
                                <?= htmlspecialchars((string) $field['label']) ?>
                            </label>
                            <input type="password" class="form-control" autocomplete="new-password"
                                   id="setting-<?= htmlspecialchars($key) ?>"
                                   name="settings[<?= htmlspecialchars($key) ?>]"
                                   value="">
                        <?php else: ?>
                            <label class="form-label" for="setting-<?= htmlspecialchars($key) ?>">
                                <?= htmlspecialchars((string) $field['label']) ?>
                            </label>
                            <input type="text" class="form-control"
                                   id="setting-<?= htmlspecialchars($key) ?>"
                                   name="settings[<?= htmlspecialchars($key) ?>]"
                                   value="<?= htmlspecialchars((string) $field['value']) ?>">
                        <?php endif; ?>
                        <?php if (!empty($field['description'])): ?>
                            <div class="text-secondary small mt-1">
                                <?= htmlspecialchars((string) $field['description']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (!$enabled): ?>
                <div class="alert alert-info mt-3 mb-0" role="alert">
                    No provider is configured yet. The switches below have no effect
                    until you pick a provider and save a site key.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <div>
                <h3 class="card-title">Protected Areas</h3>
                <div class="text-secondary small">
                    Parts of the site that ask visitors to complete the captcha.
                    Plugins register their own areas; an area only enforces once
                    a provider is configured above.
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($areas)): ?>
                <div class="text-secondary py-2">
                    No areas are registered. Plugins (and the sign-in form) register
                    captcha areas through the captcha.area extension point.
                </div>
            <?php endif; ?>
            <?php foreach ($areas as $key => $area): ?>
                <div class="mb-3">
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox"
                               id="area-<?= htmlspecialchars($key) ?>"
                               name="areas[<?= htmlspecialchars($key) ?>]"
                               value="1" <?= $area['enabled'] ? 'checked' : '' ?>>
                        <span class="form-check-label fw-medium"><?= htmlspecialchars($area['label']) ?></span>
                    </label>
                    <div class="text-secondary small ms-4">
                        <?= htmlspecialchars($area['description']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary">Save Captcha Settings</button>
        </div>
    </div>
</form>
