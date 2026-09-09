<?php
/**
 * Plugins admin page.
 *
 * Lists every discovered plugin (local + vendor) with its database-backed
 * enablement state. Toggling a plugin's switch autosaves immediately
 * (each row posts its own small form). Enabling runs its pending
 * migrations/seeds on save (see PluginsController::save()); a disabled
 * plugin runs nothing.
 *
 * Priority is read-only (it reflects the load order decided at
 * installation/first discovery, default 50) and is not user-editable.
 *
 * `required` plugins (sessions/shield/csrf) are locked: no form, the switch
 * is decorative; the server-side invariant in PluginsController::save()
 * never lets them disable.
 *
 * Trust column: the standing the Pubvana trust service (pubvanacms.com)
 * reports for the addon. Enabling goes through the trust gate, handled by
 * inline JS below: trusted activates, unknown needs a modal confirmation
 * (force=1 resubmit), malicious is refused with the service's reason. A
 * recheck button next to the badge re-asks for anything not currently
 * trusted.
 *
 * @var array $plugins Rows from PluginsController::index()
 * @var array $maliciousActive Enabled plugins the trust service flagged malicious
 * @var mixed $flash  Session flash message
 */
?>

<?php if (!empty($maliciousActive)): ?>
    <div class="alert alert-danger mb-3">
        <div class="d-flex align-items-center mb-1">
            <i class="ti ti-alert-triangle me-2"></i>
            <strong>The Pubvana trust service flagged these enabled plugins as malicious</strong>
        </div>
        <?php foreach ($maliciousActive as $row): ?>
            <div class="ms-4">
                <strong><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php if (!empty($row['warning'])): ?>
                    : <?= htmlspecialchars($row['warning'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <div class="ms-4 mt-1 text-secondary small">
            The warning shows here and on the plugin's row. Disabling or updating is up to you; the activation gate will refuse to re-enable them.
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($flash)): ?>
    <div class="alert alert-<?= str_contains((string) $flash, 'left disabled') ? 'danger' : 'info' ?> mb-3">
        <?= htmlspecialchars((string) $flash, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title d-flex align-items-center gap-2">
            <i class="ti ti-puzzle text-primary"></i>
            Plugins
        </h3>
        <div class="card-subtitle text-secondary">
            Toggling a plugin saves instantly. Enabling runs its migrations immediately; disabled plugins run nothing. Trust answers come from the Pubvana trust service.
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Plugin</th>
                    <th class="text-center" style="width:110px;">Version</th>
                    <th class="text-center" style="width:150px;">Trust</th>
                    <th class="text-center" style="width:110px;">Enabled</th>
                    <th class="text-center" style="width:100px;">Priority</th>
                    <th class="text-center" style="width:110px;">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($plugins as $plugin): ?>
                <?php $pluginIdAttr = htmlspecialchars($plugin['id'], ENT_QUOTES, 'UTF-8'); ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-sm">
                                <i class="ti ti-puzzle"></i>
                            </span>
                            <div>
                                <div class="fw-semibold"><?= htmlspecialchars($plugin['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-secondary text-sm">
                                    <?= $pluginIdAttr ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <?php if (!empty($plugin['version'])): ?>
                            <span class="text-secondary fw-semibold"><?= htmlspecialchars($plugin['version'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php else: ?>
                            <span class="text-secondary">&ndash;</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center" data-trust-cell="<?= $pluginIdAttr ?>">
                        <span data-trust-badge><?= trust_badge($plugin['trust_status'], $plugin['trust_warning'] ?? null) ?></span>
                        <?php if ($plugin['trust_status'] !== 'trusted'): ?>
                            <button type="button" class="btn btn-icon btn-sm text-secondary"
                                    data-recheck="<?= $pluginIdAttr ?>"
                                    title="Recheck with the Pubvana trust service">
                                <i class="ti ti-refresh"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($plugin['required']): ?>
                            <!-- Required: locked. The toggle is decorative; the
                                 server-side invariant in PluginsController::save()
                                 never disables required plugins. -->
                            <label class="form-check form-switch mb-0 d-flex justify-content-center" title="Required core plugin: cannot be disabled">
                                <input class="form-check-input" type="checkbox" checked disabled>
                            </label>
                        <?php else: ?>
                            <form method="post" action="/admin/plugins/save" class="d-inline-block js-trust-toggle" data-plugin-name="<?= htmlspecialchars($plugin['name'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="plugins[<?= $pluginIdAttr ?>][enabled]" value="0">
                                <label class="form-check form-switch mb-0 d-flex justify-content-center">
                                    <input class="form-check-input" type="checkbox"
                                           name="plugins[<?= $pluginIdAttr ?>][enabled]"
                                           value="1"
                                           <?= $plugin['enabled'] ? 'checked' : '' ?>
                                           title="<?= $plugin['enabled'] ? 'Disable this plugin' : 'Enable this plugin and run its migrations' ?>">
                                </label>
                            </form>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span class="text-secondary fw-semibold"><?= (int) $plugin['priority'] ?></span>
                    </td>
                    <td class="text-center">
                        <?php if ($plugin['required']): ?>
                            <span class="badge bg-yellow-lt text-yellow">Required</span>
                        <?php elseif ($plugin['enabled']): ?>
                            <span class="badge bg-green-lt text-success">Enabled</span>
                        <?php else: ?>
                            <span class="badge bg-secondary-lt text-secondary">Disabled</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Trust confirmation modal: activating something the service has not
     evaluated. Confirming resubmits the toggle form with force=1. -->
<div class="modal modal-blur fade" id="trustConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Activate without an evaluation?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>
                    <strong id="trustConfirmName"></strong>
                    <span id="trustConfirmVersion" class="text-secondary"></span>
                    hasn't been evaluated by the Pubvana trust service. It may be new, unmaintained, or offline-uncheckable.
                </p>
                <p class="text-secondary mb-0">Activate anyway?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="trustConfirmGo">Activate Anyway</button>
            </div>
        </div>
    </div>
</div>

<!-- Trust blocked modal: the service found the addon malicious. No proceed
     path on purpose; the toggle reverts when the modal closes. -->
<div class="modal modal-blur fade" id="trustBlockedModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-alert-triangle text-danger me-2"></i>
                    Activation blocked
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">
                    <strong id="trustBlockedName"></strong>
                    has been evaluated by the Pubvana trust service and found to be malicious.
                </p>
                <p id="trustBlockedWarning" class="mb-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    function esc(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    // The trust_badge() helper in badge form, warning tooltip included.
    function badgeHtml(status, warning) {
        var w = warning ? ' title="' + esc(warning) + '"' : '';
        switch (status) {
            case 'trusted':   return '<span class="badge bg-green-lt text-success"><i class="ti ti-shield-check me-1"></i>Trusted</span>';
            case 'known':     return '<span class="badge bg-azure-lt"' + w + '><i class="ti ti-shield me-1"></i>Known</span>';
            case 'malicious': return '<span class="badge bg-red-lt text-danger"' + w + '><i class="ti ti-alert-triangle me-1"></i>Malicious</span>';
            case 'unknown':   return '<span class="badge bg-yellow-lt text-yellow"><i class="ti ti-help me-1"></i>Unknown</span>';
            default:          return '<span class="badge bg-secondary-lt"><i class="ti ti-circle-dashed me-1"></i>Not checked</span>';
        }
    }

    function post(url, data) {
        data.append('_csrf_token', '<?= csrf_token() ?>');
        return fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: data
        }).then(function (r) {
            return r.json();
        });
    }

    // Recheck buttons: live answer, badge swapped in place. Only the badge
    // element is touched; the button stays. An unanswered ask (ok=false,
    // the service could not be reached) leaves the badge.
    document.querySelectorAll('[data-recheck]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            btn.classList.add('disabled');
            var data = new FormData();
            data.append('plugin', btn.getAttribute('data-recheck'));
            post('/admin/plugins/recheck', data).then(function (r) {
                var cell = document.querySelector('[data-trust-cell="' + btn.getAttribute('data-recheck') + '"]');
                if (cell && r && r.ok) {
                    var badge = cell.querySelector('[data-trust-badge]');
                    if (badge) {
                        badge.innerHTML = badgeHtml(r.status, r.warning);
                    }
                }
            }).catch(function () {
                // Leave the badge as it was; the page reload shows new answers.
            }).finally(function () {
                btn.classList.remove('disabled');
            });
        });
    });

    // Toggle forms: enables are gated by the trust service
    var pendingForm = null;
    document.querySelectorAll('form.js-trust-toggle').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var box = form.querySelector('input[type="checkbox"]');
            if (!box.checked) {
                // Disables go straight through, no gate involved
                form.submit();
                return;
            }
            post('/admin/plugins/save', new FormData(form)).then(function (r) {
                if (r && r.ok) {
                    window.location.reload();
                    return;
                }
                if (r && r.needsConfirm) {
                    pendingForm = form;
                    document.getElementById('trustConfirmName').textContent = (r.plugin && r.plugin.name) || '';
                    document.getElementById('trustConfirmVersion').textContent = (r.plugin && r.plugin.version) ? 'v' + r.plugin.version : '';
                    new bootstrap.Modal(document.getElementById('trustConfirmModal')).show();
                    return;
                }
                if (r && r.blocked) {
                    box.checked = false;
                    document.getElementById('trustBlockedName').textContent = form.getAttribute('data-plugin-name') || '';
                    document.getElementById('trustBlockedWarning').textContent = r.warning || 'No reason provided.';
                    new bootstrap.Modal(document.getElementById('trustBlockedModal')).show();
                    return;
                }
                window.location.reload();
            }).catch(function () {
                window.location.reload();
            });
        });
    });

    // Cancel (backdrop, X, or the Cancel button) must revert the toggle
    document.getElementById('trustConfirmModal').addEventListener('hidden.bs.modal', function () {
        if (pendingForm) {
            var box = pendingForm.querySelector('input[type="checkbox"]');
            if (box) {
                box.checked = false;
            }
            pendingForm = null;
        }
    });

    // Confirm: resubmit with force=1, the gate already knows we saw the warning
    var confirmBtn = document.getElementById('trustConfirmGo');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            if (!pendingForm) {
                return;
            }
            var form = pendingForm;
            pendingForm = null;
            var data = new FormData(form);
            data.append('force', '1');
            post('/admin/plugins/save', data).then(function () {
                window.location.reload();
            }).catch(function () {
                window.location.reload();
            });
        });
    }
})();
</script>
