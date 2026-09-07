<?php
/**
 * Purchases - admin page.
 *
 * @var string $pageTitle
 * @var bool $connected
 * @var array<int, array<string, mixed>> $records
 * @var string $adminBase
 */
?>

<?php if (!$connected): ?>
    <div class="alert alert-info">Connect a Pubvana account to verify and manage your purchases.</div>
<?php else: ?>
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0">Purchases</h1>
        <div>
            <form method="POST" action="<?= $adminBase ?>/verify" class="d-inline">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <button class="btn btn-outline-primary">Verify against pubvanacms.com</button>
            </form>
            <form method="POST" action="<?= $adminBase ?>/reinstall" class="d-inline">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <button class="btn btn-outline-secondary">Reinstall all</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Licensed</th>
                        <th>Scope</th>
                        <th>Installed</th>
                        <th>Version</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr><td colspan="7" class="text-center text-secondary py-4">No purchases yet. Browse the catalog and check out on pubvanacms.com.</td></tr>
                    <?php else: ?>
                        <?php foreach ($records as $r): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars((string) ($r['product_name'] ?? '')) ?></strong>
                                    <?php if (!empty($r['renews'])): ?>
                                        <div><small class="text-secondary">Renews <?= htmlspecialchars((string) $r['renews']) ?></small></div>
                                    <?php elseif (!empty($r['expires'])): ?>
                                        <div><small class="text-secondary">Expires <?= htmlspecialchars((string) $r['expires']) ?></small></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary-lt"><?= htmlspecialchars((string) ($r['item_type'] ?? '')) ?></span></td>
                                <td>
                                    <?php if (!empty($r['license_valid'])): ?>
                                        <span class="badge bg-success-lt">Yes</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-lt">No</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars((string) ($r['scope'] ?? '')) ?></td>
                                <td>
                                    <?php if (!empty($r['installed'])): ?>
                                        <span class="badge bg-success-lt">Installed</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-lt">Not installed</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars((string) ($r['installed_version'] ?? '')) ?: '—' ?></td>
                                <td class="text-end">
                                    <?php if (in_array($r['item_type'] ?? '', ['plugin', 'theme'], true)): ?>
                                        <form method="POST" action="<?= $adminBase ?>/install" class="d-inline">
                                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="product_id" value="<?= (int) ($r['store_product_id'] ?? 0) ?>">
                                            <input type="hidden" name="item_type" value="<?= htmlspecialchars((string) ($r['item_type'] ?? 'plugin')) ?>">
                                            <button class="btn btn-sm btn-primary">
                                                <?= !empty($r['installed']) ? 'Reinstall' : 'Install' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>