<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Author Profile — <?= esc($subject_user->username) ?></h1>
    <a href="<?= base_url('admin/users') ?>" class="btn btn-sm btn-outline-secondary">Back to Users</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Profile Details</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('admin/users/' . $subject_user->id . '/profile') ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Display Name</label>
                        <div class="col-sm-9">
                            <input type="text" name="display_name" class="form-control"
                                   value="<?= esc($profile->display_name ?? '') ?>"
                                   placeholder="<?= esc($subject_user->username) ?>">
                            <small class="text-muted">Shown on published posts instead of username.</small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Bio</label>
                        <div class="col-sm-9">
                            <textarea name="bio" class="form-control" rows="4"
                                      placeholder="A short bio about the author..."><?= esc($profile->bio ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Avatar</label>
                        <div class="col-sm-9">
                            <?php if (!empty($profile->avatar)): ?>
                                <div class="mb-2">
                                    <img src="<?= esc(base_url('writable/' . $profile->avatar)) ?>"
                                         alt="Current avatar" class="rounded-circle" width="80" height="80"
                                         style="object-fit:cover">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="avatar" class="form-control-file" accept="image/*">
                            <small class="text-muted">JPEG, PNG, WebP or GIF. Max 10 MB.</small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Website</label>
                        <div class="col-sm-9">
                            <input type="url" name="website" class="form-control"
                                   value="<?= esc($profile->website ?? '') ?>"
                                   placeholder="https://example.com">
                        </div>
                    </div>

                    <hr>
                    <h6 class="font-weight-bold text-gray-700 mb-3">Social Handles</h6>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"><i class="fab fa-twitter text-info"></i> Twitter / X</label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">@</span></div>
                                <input type="text" name="twitter" class="form-control"
                                       value="<?= esc($profile->twitter ?? '') ?>"
                                       placeholder="username">
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"><i class="fab fa-facebook text-primary"></i> Facebook</label>
                        <div class="col-sm-9">
                            <input type="text" name="facebook" class="form-control"
                                   value="<?= esc($profile->facebook ?? '') ?>"
                                   placeholder="profile URL or username">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"><i class="fab fa-linkedin text-primary"></i> LinkedIn</label>
                        <div class="col-sm-9">
                            <input type="text" name="linkedin" class="form-control"
                                   value="<?= esc($profile->linkedin ?? '') ?>"
                                   placeholder="profile URL or username">
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">Save Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Preview</h6>
            </div>
            <div class="card-body text-center">
                <?php
                $avatarUrl = !empty($profile->avatar)
                    ? base_url('writable/' . $profile->avatar)
                    : 'https://www.gravatar.com/avatar/' . md5(strtolower($subject_user->email ?? '')) . '?s=80&d=mp';
                ?>
                <img src="<?= esc($avatarUrl) ?>" class="rounded-circle mb-3" width="80" height="80" style="object-fit:cover" alt="">
                <h6 class="font-weight-bold"><?= esc($profile->display_name ?? $subject_user->username) ?></h6>
                <?php if (!empty($profile->bio)): ?>
                    <p class="text-muted small"><?= nl2br(esc($profile->bio)) ?></p>
                <?php endif; ?>
                <?php if (!empty($profile->website)): ?>
                    <a href="<?= esc($profile->website) ?>" class="btn btn-sm btn-outline-secondary" target="_blank">Website</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
