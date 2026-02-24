<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Enable Two-Factor Authentication</h1>
    <a href="<?= base_url('admin/users/' . $user_id . '/profile') ?>" class="btn btn-sm btn-outline-secondary">Cancel</a>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Step 1 — Scan the QR Code</h6>
            </div>
            <div class="card-body text-center">
                <p class="text-muted small mb-3">
                    Open your authenticator app (Google Authenticator, Authy, 1Password, etc.)
                    and scan this QR code.
                </p>
                <canvas id="qrcode" class="mb-3"></canvas>
                <div class="text-muted small">
                    Can't scan? Enter this code manually:
                    <div class="mt-1">
                        <code class="font-monospace text-dark" style="font-size:1rem; letter-spacing:0.15em">
                            <?= esc(chunk_split($secret, 4, ' ')) ?>
                        </code>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Step 2 — Confirm Setup</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    After scanning, enter the 6-digit code shown in your app to confirm setup.
                </p>
                <form method="POST" action="<?= base_url('admin/users/2fa/confirm') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label class="font-weight-bold">Verification Code</label>
                        <input type="text" name="totp_code"
                               class="form-control text-center font-monospace"
                               inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                               placeholder="000000" autofocus autocomplete="one-time-code"
                               style="font-size:1.4rem; letter-spacing:0.4em">
                    </div>
                    <button type="submit" class="btn btn-success btn-block">
                        <i class="fas fa-check mr-1"></i> Enable 2FA
                    </button>
                </form>
            </div>
        </div>

        <div class="alert alert-warning small">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            <strong>Store your recovery codes.</strong> If you lose access to your authenticator app,
            you will not be able to log in. Contact your site administrator to reset 2FA.
        </div>
    </div>
</div>

<?php
$uriJson = json_encode($provisioning_uri);
$content = ob_get_clean();
$content .= '<script>window._totpUri=' . $uriJson . ';</script>';
?>
<?php $extra_scripts = <<<'HTML'
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.4/build/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    QRCode.toCanvas(document.getElementById('qrcode'), window._totpUri, {
        width: 220,
        margin: 2,
        color: { dark: '#1a2744', light: '#ffffff' }
    });
});
</script>
HTML;
?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
