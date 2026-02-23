<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>
<form method="POST" action="<?= base_url('contact') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" value="<?= esc(old('name')) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= esc(old('email')) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Message</label>
        <textarea name="message" class="form-control" rows="6" required><?= esc(old('message')) ?></textarea>
    </div>
    <?php if (getenv('HCAPTCHA_SITE_KEY')): ?>
        <div class="h-captcha mb-3" data-sitekey="<?= esc(getenv('HCAPTCHA_SITE_KEY')) ?>"></div>
        <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
    <?php endif; ?>
    <button type="submit" class="btn btn-primary">Send Message</button>
</form>
