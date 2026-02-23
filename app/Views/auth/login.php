<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Auth.login') ?><?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="container d-flex justify-content-center p-5">
    <div class="card col-12 col-md-5 shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-4"><?= lang('Auth.login') ?></h5>

            <?php if (session('error') !== null): ?>
                <div class="alert alert-danger" role="alert"><?= esc(session('error')) ?></div>
            <?php elseif (session('errors') !== null): ?>
                <div class="alert alert-danger" role="alert">
                    <?php if (is_array(session('errors'))): ?>
                        <?php foreach (session('errors') as $error): ?>
                            <?= esc($error) ?><br>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?= esc(session('errors')) ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (session('message') !== null): ?>
                <div class="alert alert-success" role="alert"><?= esc(session('message')) ?></div>
            <?php endif; ?>

            <form action="<?= url_to('login') ?>" method="post">
                <?= csrf_field() ?>

                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="floatingEmailInput" name="email"
                           inputmode="email" autocomplete="email"
                           placeholder="<?= lang('Auth.email') ?>" value="<?= old('email') ?>" required>
                    <label for="floatingEmailInput"><?= lang('Auth.email') ?></label>
                </div>

                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="floatingPasswordInput" name="password"
                           inputmode="text" autocomplete="current-password"
                           placeholder="<?= lang('Auth.password') ?>" required>
                    <label for="floatingPasswordInput"><?= lang('Auth.password') ?></label>
                </div>

                <?php if (setting('Auth.sessionConfig')['allowRemembering']): ?>
                    <div class="form-check mb-2">
                        <label class="form-check-label">
                            <input type="checkbox" name="remember" class="form-check-input"
                                   <?php if (old('remember')): ?> checked<?php endif; ?>>
                            <?= lang('Auth.rememberMe') ?>
                        </label>
                    </div>
                <?php endif; ?>

                <div class="d-grid col-12 col-md-8 mx-auto mt-3 mb-2">
                    <button type="submit" class="btn btn-primary btn-block"><?= lang('Auth.login') ?></button>
                </div>

                <?php if (setting('Auth.allowMagicLinkLogins')): ?>
                    <p class="text-center small"><?= lang('Auth.forgotPassword') ?>
                        <a href="<?= url_to('magic-link') ?>"><?= lang('Auth.useMagicLink') ?></a></p>
                <?php endif; ?>

                <?php if (setting('Auth.allowRegistration')): ?>
                    <p class="text-center small"><?= lang('Auth.needAccount') ?>
                        <a href="<?= url_to('register') ?>"><?= lang('Auth.register') ?></a></p>
                <?php endif; ?>
            </form>

            <?php
            $socialConfig = new \Config\Social();
            $hasGoogle    = (bool) $socialConfig->googleClientId;
            $hasFacebook  = (bool) $socialConfig->facebookClientId;
            if ($hasGoogle || $hasFacebook):
            ?>
            <hr>
            <p class="text-center text-muted small mb-2">Or sign in with</p>
            <div class="d-flex justify-content-center gap-2">
                <?php if ($hasGoogle): ?>
                <a href="<?= base_url('auth/social/google') ?>" class="btn btn-outline-danger btn-sm">
                    <i class="fab fa-google mr-1"></i> Google
                </a>
                <?php endif; ?>
                <?php if ($hasFacebook): ?>
                <a href="<?= base_url('auth/social/facebook') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fab fa-facebook mr-1"></i> Facebook
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
