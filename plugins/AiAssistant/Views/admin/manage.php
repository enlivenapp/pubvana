<?php
/**
 * AI Assistant management: API keys, grants, default author, audit log.
 *
 * @var string $pageTitle
 * @var string $adminBase          admin URL base for this plugin
 * @var array  $keys            listKeys() rows
 * @var array  $helpGroups      AiService::helpGroups() display groups
 * @var int    $logLimit
 * @var array  $logs            recentLogs() rows
 * @var int    $defaultAuthorId
 * @var \Enlivenapp\FlightShield\Models\User[] $activeUsers
 */

$session = \Flight::app()->session();
$plainToken = $session->pullFlash('plain_token');
$apiBase = rtrim((string) \Flight::app()->pluginLoader()->routePrefix('pubvana/ai'), '/');
?>

<div class="d-flex justify-content-end mb-3">
    <a href="<?= $adminBase ?>/help" class="btn btn-primary">
        <i class="ti ti-book-2 me-1"></i> Quick Start &amp; Help
    </a>
</div>

<?php if ($plainToken !== null): ?>
    <div class="alert alert-warning alert-dismissible" role="alert">
        <div class="d-flex align-items-center">
            <i class="ti ti-alert-triangle icon alert-icon"></i>
            <div>
                <strong>Copy this API key now.</strong> It is shown only once and cannot be recovered.
                <div class="mt-2">
                    <code id="plain-token" class="form-control form-control-sm user-select-all"><?= htmlspecialchars($plainToken) ?></code>
                </div>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

        <div class="mt-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="small fw-semibold text-uppercase text-secondary">AI startup text</span>
                <button type="button" class="btn btn-sm btn-outline-primary" id="copy-startup">
                    <i class="ti ti-copy me-1"></i> Copy startup text
                </button>
            </div>
            <textarea id="startup-text" class="form-control font-monospace user-select-all" rows="4"
                      readonly onclick="this.select()">GET <?= htmlspecialchars($siteUrl . $apiBase) ?>/help
Authorization: Bearer <?= htmlspecialchars($plainToken) ?></textarea>
        </div>
    </div>
    <script>
    (function () {
        var textarea = document.getElementById('startup-text');
        var button = document.getElementById('copy-startup');
        if (!textarea || !button) {
            return;
        }
        function reset() {
            button.innerHTML = '<i class="ti ti-copy me-1"></i> Copy startup text';
        }
        function done() {
            button.textContent = 'Copied!';
            setTimeout(reset, 1500);
        }
        button.addEventListener('click', function () {
            textarea.select();
            if (navigator.clipboard) {
                navigator.clipboard.writeText(textarea.value).then(done, fallback);
            } else {
                fallback();
            }
            function fallback() {
                try {
                    document.execCommand('copy');
                    done();
                } catch (e) {
                    reset();
                }
            }
        });
    })();
    </script>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Create API Key</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= $adminBase ?>/manage/keys" class="row g-2 align-items-end">
                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                    <div class="col">
                        <label class="form-label" for="key-name">Key name</label>
                        <input type="text" name="name" id="key-name" class="form-control" maxlength="120"
                               placeholder="e.g. my-blog-assistant" required>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i> Generate
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Default Author</h3>
            </div>
            <div class="card-body">
                <p class="text-secondary small">
                    AI-created posts and pages are attributed to this user.
                </p>
                <form method="POST" action="<?= $adminBase ?>/manage/author" class="row g-2 align-items-end">
                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                    <div class="col">
                        <select name="author_id" class="form-select">
                            <?php foreach ($activeUsers as $user): ?>
                                <option value="<?= (int) $user->id ?>" <?= (int) $user->id === $defaultAuthorId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) $user->username) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-outline-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">API Keys</h3>
            </div>
            <?php if (empty($keys)): ?>
                <div class="card-body">
                    <p class="text-center text-secondary py-3 mb-0">No API keys yet. Generate one to connect your AI assistant.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Granted</th>
                                <th>Status</th>
                                <th>Last Used</th>
                                <th class="w-1">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($keys as $key): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($key['name']) ?></div>
                                        <code class="text-secondary"><?= htmlspecialchars($key['key_prefix']) ?>…</code>
                                    </td>
                                    <td>
                                        <?php if (empty($key['grants'])): ?>
                                            <span class="badge bg-secondary-lt">none</span>
                                        <?php else: ?>
                                            <span class="badge bg-blue-lt"><?= count($key['grants']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($key['blocked']): ?>
                                            <span class="badge bg-danger-lt" title="Blocked until <?= htmlspecialchars((string) $key['blocked_until']) ?>">
                                                Blocked
                                            </span>
                                        <?php elseif ($key['enabled']): ?>
                                            <span class="badge bg-success-lt">Enabled</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-lt">Disabled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-secondary">
                                        <?= $key['last_used_at'] !== null ? date('M j, Y g:ia', strtotime($key['last_used_at'])) : 'Never' ?>
                                    </td>
                                    <td>
                                        <div class="btn-list flex-nowrap">
                                            <button class="btn btn-sm btn-outline-primary" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#grants-<?= (int) $key['id'] ?>"
                                                    aria-expanded="false" aria-controls="grants-<?= (int) $key['id'] ?>">
                                                Grants
                                            </button>
                                            <form method="POST" action="<?= $adminBase ?>/manage/keys/<?= (int) $key['id'] ?>/toggle" class="d-inline">
                                                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                                <button class="btn btn-sm btn-outline-secondary">
                                                    <?= $key['enabled'] ? 'Disable' : 'Enable' ?>
                                                </button>
                                            </form>
                                            <form method="POST" action="<?= $adminBase ?>/manage/keys/<?= (int) $key['id'] ?>/delete" class="d-inline"
                                                  onsubmit="return confirm('Delete this API key? Any client using it will be barred.')">
                                                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="collapse" id="grants-<?= (int) $key['id'] ?>">
                                    <td colspan="5">
                                        <form method="POST" action="<?= $adminBase ?>/manage/keys/<?= (int) $key['id'] ?>/grants">
                                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                            <div class="row">
                                                <?php foreach ($helpGroups as $group => $permissions): ?>
                                                    <div class="col-md-4">
                                                        <div class="mb-2 fw-semibold text-uppercase text-secondary small">
                                                            <?= htmlspecialchars($group) ?>
                                                        </div>
                                                        <?php foreach ($permissions as $permission => $meta): ?>
                                                            <label class="form-check">
                                                                <input type="checkbox" class="form-check-input"
                                                                       name="grants[]" value="<?= htmlspecialchars($permission) ?>"
                                                                       <?= in_array($permission, $key['grants'], true) ? 'checked' : '' ?>>
                                                                <span class="form-check-label">
                                                                    <?= htmlspecialchars($meta['label']) ?>
                                                                    <code class="d-block small text-secondary"><?= htmlspecialchars($permission) ?></code>
                                                                </span>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="mt-3 d-flex justify-content-end">
                                                <button class="btn btn-sm btn-primary">Save Grants</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Audit Log <span class="text-secondary fw-normal">(last <?= (int) $logLimit ?>)</span></h3>
    </div>
    <?php if (empty($logs)): ?>
        <div class="card-body">
            <p class="text-center text-secondary py-3 mb-0">No API activity yet.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Key</th>
                        <th>Request</th>
                        <th>Outcome</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="text-secondary text-nowrap"><?= date('M j, Y g:ia', strtotime($log['created_at'])) ?></td>
                            <td>
                                <?= $log['key_name'] !== null ? htmlspecialchars($log['key_name']) : '<em class="text-secondary">anonymous</em>' ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary-lt"><?= htmlspecialchars($log['method']) ?></span>
                                <code><?= htmlspecialchars($log['endpoint']) ?></code>
                                <?php if ($log['entity_type'] !== null): ?>
                                    <span class="text-secondary small">→ <?= htmlspecialchars($log['entity_type']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $tone = match ($log['outcome']) {
                                    'ok'     => 'success',
                                    'denied' => 'warning',
                                    default  => 'danger',
                                }; ?>
                                <span class="badge bg-<?= $tone ?>-lt"><?= htmlspecialchars($log['outcome']) ?></span>
                            </td>
                            <td class="text-secondary small">
                                <?= $log['detail'] !== null ? htmlspecialchars($log['detail']) : '' ?>
                                <?php if ($log['ip'] !== null): ?>
                                    <div class="text-secondary text-muted"><?= htmlspecialchars($log['ip']) ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>