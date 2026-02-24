<?php if (!empty($update['available'])): ?>
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <strong><i class="fas fa-arrow-circle-up mr-1"></i> Pubvana <?= esc($update['latest_version']) ?> is available</strong>
    — you are running <?= esc($update['current_version']) ?>.
    <div class="mt-2">
        <?php if (!empty($update['release_url'])): ?>
        <a href="<?= esc($update['release_url']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-dark mr-1">
            <i class="fas fa-tag fa-sm"></i> Release Notes
        </a>
        <?php endif; ?>
        <?php if (!empty($update['zipball_url'])): ?>
        <a href="<?= esc($update['zipball_url']) ?>" class="btn btn-sm btn-warning mr-1">
            <i class="fas fa-download fa-sm"></i> Download ZIP
        </a>
        <?php endif; ?>
        <a href="<?= base_url('admin/updates') ?>" class="btn btn-sm btn-secondary">
            <i class="fas fa-info-circle fa-sm"></i> Details
        </a>
    </div>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>
