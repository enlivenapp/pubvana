<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Two-Factor Authentication — <?= esc(setting('App.siteName') ?? 'Pubvana') ?></title>
    <link href="<?= base_url('assets/admin/vendor/fontawesome-free/css/all.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/admin/css/sb-admin-2.min.css') ?>" rel="stylesheet">
</head>
<body class="bg-gradient-primary">

<div class="container">
    <div class="row justify-content-center" style="padding-top: 8vh">
        <div class="col-xl-5 col-lg-6 col-md-8">

            <div class="card o-hidden border-0 shadow-lg">
                <div class="card-body p-0">
                    <div class="p-5">

                        <div class="text-center mb-4">
                            <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                            <h1 class="h4 text-gray-900 font-weight-bold">Two-Factor Authentication</h1>
                            <p class="text-muted small">
                                Enter the 6-digit code from your authenticator app.
                            </p>
                        </div>

                        <?php if (! empty($error)): ?>
                            <div class="alert alert-danger"><?= esc($error) ?></div>
                        <?php endif; ?>

                        <form method="POST" action="<?= base_url('auth/2fa') ?>">
                            <?= csrf_field() ?>

                            <div class="form-group">
                                <input type="text" name="totp_code" class="form-control form-control-lg text-center font-monospace"
                                       inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                                       placeholder="000000" autofocus autocomplete="one-time-code"
                                       style="letter-spacing: 0.4em; font-size: 1.5rem;">
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-lg">
                                Verify
                            </button>
                        </form>

                        <hr>
                        <div class="text-center">
                            <a href="<?= base_url('logout') ?>" class="small text-muted">
                                <i class="fas fa-sign-out-alt mr-1"></i>Sign in as a different user
                            </a>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="<?= base_url('assets/admin/vendor/jquery/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/admin/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
