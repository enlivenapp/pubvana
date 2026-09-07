<?php
/**
 * Marketplace overview - admin page.
 *
 * @var string $pageTitle
 * @var bool $connected
 * @var string $accountEmail
 * @var array<int, array<string, mixed>> $categories
 * @var array<int, array<string, mixed>> $items
 * @var string $adminBase
 */
?>

<?php if (!$connected): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Connect a Pubvana account</h3>
        </div>
        <div class="card-body">
            <p>
                The Marketplace is the companion app for the Pubvana Digital Store at
                pubvanacms.com. Link this site to your Pubvana account to browse the
                catalog, push items into your account-bound cart, and install and
                reinstall what you buy.
            </p>
            <p class="alert alert-warning">
                Periodic verification: about every two weeks this site connects to
                pubvanacms.com to verify your purchases and license status for this
                domain. No payment takes place on this site; checkout happens on
                pubvanacms.com in a new tab.
            </p>
            <form method="POST" action="<?= $adminBase ?>/connect" class="row g-2">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <div class="col-auto">
                    <input type="email" name="email" class="form-control" placeholder="you@example.com" required autofocus>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary">Create or sign in</button>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-0 mb-2">Marketplace</h1>
            <div class="text-secondary">
                Connected as <strong><?= htmlspecialchars($accountEmail) ?></strong>.
                Purchases are bound to this domain.
            </div>
        </div>
        <div>
            <a href="<?= $adminBase ?>/purchases" class="btn btn-outline-primary">Purchases</a>
            <form method="POST" action="<?= $adminBase ?>/disconnect" class="d-inline">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <button class="btn btn-ghost-secondary">Disconnect</button>
            </form>
        </div>
    </div>

    <?php if (!empty($items)): ?>
        <div class="row g-3">
            <?php foreach ($items as $item):
                $id = (int) ($item['id'] ?? 0);
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-secondary-lt"><?= htmlspecialchars((string) ($item['item_type'] ?? '')) ?></span>
                                <?php if (!empty($item['is_free'])): ?>
                                    <span class="badge bg-success-lt">Free</span>
                                <?php endif; ?>
                            </div>
                            <h3 class="card-title mb-1"><?= htmlspecialchars((string) ($item['name'] ?? '')) ?></h3>
                            <p class="card-text text-secondary"><?= htmlspecialchars((string) ($item['summary'] ?? '')) ?></p>
                            <div class="d-flex align-items-center justify-content-between mt-3">
                                <strong><?= htmlspecialchars((string) ($item['currency'] ?? 'USD')) ?> <?= number_format((float) ($item['price'] ?? 0), 2) ?></strong>
                                <button type="button" class="btn btn-primary"
                                        data-cart-product="<?= $id ?>"
                                        data-cart-name="<?= htmlspecialchars((string) ($item['name'] ?? '')) ?>">
                                    Add to cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-4">
            <a href="<?= $adminBase ?>/cart-open" target="_blank" class="btn btn-lg btn-success w-100">
                Purchase on pubvanacms.com
            </a>
            <p class="text-secondary small text-center mt-2 mb-0">
                Opens the Pubvana website checkout in a new tab with your cart already loaded.
            </p>
        </div>
    <?php else: ?>
        <p class="text-secondary">The catalog is empty or the store is unreachable.</p>
    <?php endif; ?>

    <script>
        document.querySelectorAll('[data-cart-product]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-cart-product');
                var name = btn.getAttribute('data-cart-name');
                btn.disabled = true;
                btn.textContent = 'Adding…';
                var fd = new FormData();
                fd.append('product_id', id);
                fd.append('currency', 'USD');
                fd.append('_csrf_token', '<?= csrf_token() ?>');
                fetch('<?= $adminBase ?>/cart-add', {
                    method: 'POST',
                    body: fd,
                    headers: { 'Accept': 'application/json' }
                }).then(function (r) { return r.json(); }).then(function (res) {
                    if (res && res.ok) {
                        btn.textContent = 'Added to cart';
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-success');
                    } else {
                        btn.textContent = 'Try again';
                        btn.disabled = false;
                    }
                }).catch(function () {
                    btn.textContent = 'Try again';
                    btn.disabled = false;
                });
            });
        });
    </script>
<?php endif; ?>