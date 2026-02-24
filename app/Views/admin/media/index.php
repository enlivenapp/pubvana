<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Media Library</h1>
</div>

<!-- Upload area -->
<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Upload Media</h6></div>
    <div class="card-body">
        <div id="upload-zone" class="border border-dashed rounded p-4 text-center bg-light">
            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
            <p class="mb-2">Drag &amp; drop files here, or</p>
            <input type="file" id="file-input" accept="image/*" multiple class="d-none">
            <button class="btn btn-primary" onclick="document.getElementById('file-input').click()">Choose Files</button>
        </div>
        <div id="upload-progress" class="mt-2 d-none">
            <div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div></div>
            <p class="text-muted small mt-1">Uploading…</p>
        </div>
    </div>
</div>

<!-- Media grid -->
<div class="row" id="media-grid">
    <?php foreach ($media as $item): ?>
    <div class="col-6 col-md-3 col-lg-2 mb-3 media-item" id="media-<?= $item->id ?>">
        <div class="card h-100 border shadow-sm">
            <img src="<?= esc(base_url('writable/' . $item->path)) ?>" class="card-img-top" style="height:100px;object-fit:cover" alt="<?= esc($item->filename) ?>">
            <div class="card-body p-1 text-center">
                <p class="text-truncate small mb-1" title="<?= esc($item->filename) ?>"><?= esc($item->filename) ?></p>
                <p class="text-muted" style="font-size:0.7rem"><?= round($item->size/1024, 1) ?> KB</p>
                <form method="POST" action="<?= base_url('admin/media/' . $item->id . '/delete') ?>" onsubmit="return confirm('Delete?')">
                    <?= csrf_field() ?>
                    <button class="btn btn-xs btn-outline-danger btn-block">Delete</button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($media)): ?><div class="col-12"><p class="text-muted text-center py-4">No media yet.</p></div><?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php ob_start(); ?>
<script>
document.getElementById('file-input').addEventListener('change', function(e) {
    var files = e.target.files;
    if (!files.length) return;
    var progress = document.getElementById('upload-progress');
    progress.classList.remove('d-none');
    var formData = new FormData();
    for (var i = 0; i < files.length; i++) { formData.append('file', files[i]); }
    formData.append('csrf_test_name', '<?= csrf_hash() ?>');
    fetch('<?= base_url('admin/media/upload') ?>', {
        method: 'POST',
        body: formData
    }).then(r => r.json()).then(data => {
        progress.classList.add('d-none');
        if (data.success) { location.reload(); }
        else { alert('Upload failed: ' + (data.error || 'Unknown error')); }
    }).catch(err => { progress.classList.add('d-none'); alert('Upload error: ' + err); });
});
</script>
<?php $extra_scripts = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
