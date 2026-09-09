<?php
/**
 * Theme listing — card grid with screenshot, name, activate button.
 *
 * Trust badge: the standing the Pubvana trust service (pubvanacms.com)
 * reports for the theme. Activating goes through the trust gate, handled by
 * inline JS below: trusted activates, unknown needs a modal confirmation
 * (force=1 resubmit), malicious is refused with the service's reason. A
 * recheck button sits on every card for anything not currently trusted.
 *
 * @var object[] $themes       All themes from DB
 * @var array    $validation   folder => isValid
 * @var array    $theme_info   folder => pubvana.json data
 * @var array    $trust        folder => ['status' => string, 'warning' => ?string]
 * @var array    $maliciousActive Active themes the trust service flagged
 */
?>

<?php if (!empty($maliciousActive)): ?>
    <div class="alert alert-danger mb-3">
        <div class="d-flex align-items-center mb-1">
            <i class="ti ti-alert-triangle me-2"></i>
            <strong>The Pubvana trust service flagged these active themes as malicious</strong>
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
            The warning shows here and on the theme's card. Switching themes is up to you; the activation gate will refuse to re-activate them.
        </div>
    </div>
<?php endif; ?>

<div class="row row-cards">
<?php foreach ($themes as $theme): ?>
    <?php
    $isValid = $validation[$theme->folder] ?? true;
    $info = $theme_info[$theme->folder] ?? [];
    $screenshotUrl = '';
    if (!empty($theme->screenshot)) {
        $screenshotUrl = '/themes/' . $theme->folder . '/' . $theme->screenshot;
    } elseif (!empty($info['icon'])) {
        $screenshotUrl = '/themes/' . $theme->folder . '/' . $info['icon'];
    }

    $regions = $info['provides']['regions'] ?? [];
    $options = $info['provides']['options'] ?? [];
    $trustInfo = $trust[$theme->folder] ?? ['status' => 'none', 'warning' => null];
    ?>
    <div class="col-sm-6 col-lg-4">
        <div class="card<?= $theme->is_active ? ' border-primary' : '' ?>">
            <?php if ($screenshotUrl): ?>
                <img src="<?= $screenshotUrl ?>" class="card-img-top" alt="<?= htmlspecialchars($theme->name) ?>" style="height:200px;object-fit:cover">
            <?php else: ?>
                <div class="card-img-top bg-primary-lt d-flex align-items-center justify-content-center" style="height:200px">
                    <i class="ti ti-palette" style="font-size:3rem;opacity:.5"></i>
                </div>
            <?php endif; ?>

            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h3 class="card-title mb-0"><?= htmlspecialchars($theme->name) ?></h3>
                    <div class="d-flex gap-1">
                        <span data-trust-cell><?= trust_badge($trustInfo['status'], $trustInfo['warning']) ?></span>
                        <?php if ($theme->is_active): ?>
                            <span class="badge bg-primary-lt">Active</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($theme->description)): ?>
                    <p class="text-secondary small"><?= htmlspecialchars($theme->description) ?></p>
                <?php endif; ?>

                <p class="text-secondary small mb-2">
                    <?php if (!empty($theme->author)): ?>
                        By <?= htmlspecialchars($theme->author) ?>
                    <?php endif; ?>
                    <?php if (!empty($theme->version)): ?>
                        &middot; v<?= htmlspecialchars($theme->version) ?>
                    <?php endif; ?>
                </p>

                <?php if (!empty($regions)): ?>
                <div class="mb-2">
                    <small class="text-secondary d-block mb-1">Regions</small>
                    <?php foreach ($regions as $region): ?>
                        <span class="badge bg-azure-lt me-1 mb-1"><?= htmlspecialchars($region['label'] ?? $region['id']) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($options)): ?>
                <div class="mb-2">
                    <small class="text-secondary d-block mb-1">Options</small>
                    <?php foreach ($options as $key => $opt): ?>
                        <span class="badge bg-purple-lt me-1 mb-1"><?= htmlspecialchars($opt['label'] ?? $key) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!$isValid): ?>
                    <div class="alert alert-danger small py-1 px-2 mt-2 mb-0">
                        <i class="ti ti-alert-triangle"></i> PHP detected in theme files, activation blocked.
                    </div>
                <?php endif; ?>

                <?php if (!empty($theme->disabled)): ?>
                    <div class="alert alert-danger small py-1 px-2 mt-2 mb-0">
                        <i class="ti ti-ban"></i> Disabled: <?= htmlspecialchars($theme->disabled_reason) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card-footer d-flex justify-content-between">
                <div>
                    <?php if (!$theme->is_active && $isValid && empty($theme->disabled)): ?>
                    <form method="post" action="/admin/themes/<?= (int) $theme->id ?>/activate"
                          class="d-inline js-trust-activate"
                          data-theme-id="<?= (int) $theme->id ?>"
                          data-theme-name="<?= htmlspecialchars($theme->name, ENT_QUOTES, 'UTF-8') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-primary btn-sm">Activate</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($trustInfo['status'] !== 'trusted'): ?>
                    <button type="button" class="btn btn-icon btn-sm text-secondary"
                            data-theme-recheck="<?= (int) $theme->id ?>"
                            title="Recheck with the Pubvana trust service">
                        <i class="ti ti-refresh"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if (!empty($options)): ?>
                    <a href="/admin/themes/<?= (int) $theme->id ?>/options" class="btn btn-outline-secondary btn-sm">Options</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php if (empty($themes)): ?>
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center text-secondary py-4">
                No themes found. Add a theme to the <code>themes/</code> directory.
            </div>
        </div>
    </div>
<?php endif; ?>
</div>

<!-- Trust confirmation modal: activating something the service has not
     evaluated. Confirming resubmits the activate form with force=1. -->
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

<!-- Trust blocked modal: the service found the theme malicious. No proceed
     path on purpose. -->
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

    function esc(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    // Recheck buttons: live answer, badge swapped in place
    document.querySelectorAll('[data-theme-recheck]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var cardBody = btn.closest('.card-body');
            var cell = cardBody ? cardBody.querySelector('[data-trust-cell]') : null;
            btn.classList.add('disabled');
            post('/admin/themes/' + btn.getAttribute('data-theme-recheck') + '/recheck', new FormData())
                .then(function (r) {
                    if (cell && r && r.ok) {
                        cell.innerHTML = badgeHtml(r.status, r.warning);
                    }
                })
                .catch(function () {
                    // Leave the badge as it was.
                })
                .finally(function () {
                    btn.classList.remove('disabled');
                });
        });
    });

    // Activate forms: gated by the trust service
    var pendingForm = null;
    document.querySelectorAll('form.js-trust-activate').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var url = '/admin/themes/' + form.getAttribute('data-theme-id') + '/activate';
            post(url, new FormData(form)).then(function (r) {
                if (r && r.ok) {
                    window.location.reload();
                    return;
                }
                if (r && r.needsConfirm) {
                    pendingForm = form;
                    document.getElementById('trustConfirmName').textContent = (r.theme && r.theme.name) || '';
                    document.getElementById('trustConfirmVersion').textContent = (r.theme && r.theme.version) ? 'v' + r.theme.version : '';
                    new bootstrap.Modal(document.getElementById('trustConfirmModal')).show();
                    return;
                }
                if (r && r.blocked) {
                    document.getElementById('trustBlockedName').textContent = form.getAttribute('data-theme-name') || '';
                    document.getElementById('trustBlockedWarning').textContent = r.warning || 'No reason provided.';
                    new bootstrap.Modal(document.getElementById('trustBlockedModal')).show();
                    return;
                }
                // Server-side activation failure (not trust related): the
                // controller flashes the error, the reload shows it.
                window.location.reload();
            }).catch(function () {
                window.location.reload();
            });
        });
    });

    // Cancel (backdrop, X, or the Cancel button) closes the modal; nothing
    // was activated, the card just stays as it was.
    document.getElementById('trustConfirmModal').addEventListener('hidden.bs.modal', function () {
        pendingForm = null;
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
            post('/admin/themes/' + form.getAttribute('data-theme-id') + '/activate', data)
                .then(function () {
                    window.location.reload();
                })
                .catch(function () {
                    window.location.reload();
                });
        });
    }
})();
</script>
