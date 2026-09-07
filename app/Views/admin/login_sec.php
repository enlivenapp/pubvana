<?php
/**
 * Login settings page (Settings > Login) - standalone, not a tab.
 *
 * Shield sign-in and registration toggles. Each field is a boolean stored
 * under the Shield.* settings namespace and folded onto the flight-shield
 * config by PluginLoader at boot.
 *
 * @var string $pageTitle
 * @var array<int, array<string, mixed>> $fields Declared checkbox fields with resolved values
 * @var string|null $flash One-shot save message
 */
?>

<?php if ($flash !== null): ?>
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

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Authentication Features</h3>
            </div>
            <form method="POST" action="/admin/login-sec/save" autocomplete="off">
                <?= csrf_field() ?>
                <div class="card-body">
                    <?php if (empty($fields)): ?>
                        <div class="text-secondary">No login settings declared.</div>
                    <?php endif; ?>
                    <?php foreach ($fields as $field): ?>
                        <?php $key = (string) $field['key']; ?>
                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                       id="setting-<?= htmlspecialchars($key) ?>"
                                       name="settings[<?= htmlspecialchars($key) ?>]"
                                       value="1" <?= !empty($field['value']) ? 'checked' : '' ?>>
                                <span class="form-check-label fw-medium">
                                    <?= htmlspecialchars((string) $field['label']) ?>
                                </span>
                            </label>
                            <?php if (!empty($field['description'])): ?>
                                <div class="text-secondary small ms-4">
                                    <?= htmlspecialchars((string) $field['description']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">Save Login Settings</button>
                </div>
            </form>
        </div>

        <div class="alert alert-info" role="alert">
            <div class="d-flex">
                <div>
                    <i class="ti ti-info-circle icon alert-icon"></i>
                </div>
                <div>
                    Changes apply the next time anyone visits the site. Features
                    that send email (sign-in codes, account activation, magic
                    links) need delivery set up under
                    <a href="/admin/email">Tools &gt; Email</a>.
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Related Tools</h3>
            </div>
            <div class="list-group list-group-flush">
                <a href="/admin/users" class="list-group-item list-group-item-action">
                    <span class="me-2"><i class="ti ti-users"></i></span> Users
                    <span class="text-secondary small d-block ms-4">Ban, force password reset, and manage accounts.</span>
                </a>
                <a href="/admin/groups" class="list-group-item list-group-item-action">
                    <span class="me-2"><i class="ti ti-users-group"></i></span> Groups
                    <span class="text-secondary small d-block ms-4">Roles and group permission sets.</span>
                </a>
                <a href="/admin/permissions" class="list-group-item list-group-item-action">
                    <span class="me-2"><i class="ti ti-lock"></i></span> Permissions
                    <span class="text-secondary small d-block ms-4">Fine-grained permission aliases.</span>
                </a>
                <a href="/admin/email" class="list-group-item list-group-item-action">
                    <span class="me-2"><i class="ti ti-mail"></i></span> Email
                    <span class="text-secondary small d-block ms-4">SMTP transport for auth emails.</span>
                </a>
            </div>
        </div>
    </div>
</div>
