<?php
/**
 * Lightweight avatar picker — upload or remove, no media library.
 *
 * @var string $inputName    Form input name
 * @var string $currentValue Current image path
 * @var string $pickerId     Unique ID for this picker instance
 * @var string $adminBase    Admin URL base for this plugin's admin routes
 */
$hasImage = !empty($currentValue);
$previewSrc = $hasImage ? '/' . ltrim($currentValue, '/') : '';
?>
<div class="avatar-picker" id="<?= $pickerId ?>">
    <div class="d-inline-block mb-2">
        <div id="<?= $pickerId ?>-preview" class="rounded overflow-hidden border"
             style="width:120px; height:120px;">
            <?php if ($hasImage): ?>
                <img src="<?= htmlspecialchars($previewSrc) ?>" alt="Avatar"
                     style="width:100%; height:100%; object-fit:cover;">
            <?php else: ?>
                <div class="d-flex align-items-center justify-content-center bg-light"
                     style="width:100%; height:100%;">
                    <i class="ti ti-user" style="font-size:2.5rem; color:#adb5bd;"></i>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <input type="hidden" name="<?= htmlspecialchars($inputName) ?>" id="<?= $pickerId ?>-input"
           value="<?= htmlspecialchars($currentValue) ?>">

    <div id="<?= $pickerId ?>-zone" class="border border-dashed rounded p-3 text-center mb-2"
         style="cursor:pointer; max-width:300px;">
        <i class="ti ti-cloud-upload" style="font-size:1.2rem;"></i>
        <p class="mb-0 mt-1 small text-secondary">Drop image or click to upload</p>
        <input type="file" class="d-none" accept="image/jpeg,image/png,image/gif,image/webp">
    </div>

    <?php if ($hasImage): ?>
        <button type="button" class="btn btn-outline-danger btn-sm avatar-picker-remove">
            <i class="ti ti-x"></i> Remove
        </button>
    <?php endif; ?>
</div>

<script>
(function() {
    var wrap     = document.getElementById('<?= $pickerId ?>');
    var preview  = document.getElementById('<?= $pickerId ?>-preview');
    var hidden   = document.getElementById('<?= $pickerId ?>-input');
    var zone     = document.getElementById('<?= $pickerId ?>-zone');
    var fileInput = zone.querySelector('input[type="file"]');
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var adminBase = '<?= $adminBase ?>';

    zone.addEventListener('click', function(e) {
        if (e.target.closest('input')) return;
        fileInput.click();
    });
    zone.addEventListener('dragover', function(e) {
        e.preventDefault();
        zone.classList.add('border-primary');
    });
    zone.addEventListener('dragleave', function() {
        zone.classList.remove('border-primary');
    });
    zone.addEventListener('drop', function(e) {
        e.preventDefault();
        zone.classList.remove('border-primary');
        if (e.dataTransfer.files.length) uploadFile(e.dataTransfer.files[0]);
    });
    fileInput.addEventListener('change', function() {
        if (fileInput.files.length) uploadFile(fileInput.files[0]);
        fileInput.value = '';
    });

    function uploadFile(file) {
        var fd = new FormData();
        fd.append('file', file);
        fd.append('_csrf_token', csrfToken);
        fetch(adminBase + '/upload/image', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error) { alert(data.error); return; }
                var url = data.medium_url || data.url || '';
                hidden.value = url;
                preview.innerHTML = '<img src="' + url + '" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">';
                ensureRemoveBtn();
            })
            .catch(function() { alert('Upload failed.'); });
    }

    function ensureRemoveBtn() {
        if (wrap.querySelector('.avatar-picker-remove')) return;
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-outline-danger btn-sm avatar-picker-remove';
        btn.innerHTML = '<i class="ti ti-x"></i> Remove';
        btn.addEventListener('click', removeImage);
        zone.insertAdjacentElement('afterend', btn);
    }

    function removeImage() {
        hidden.value = '';
        preview.innerHTML = '<div class="d-flex align-items-center justify-content-center bg-light" style="width:100%;height:100%;">'
            + '<i class="ti ti-user" style="font-size:2.5rem;color:#adb5bd;"></i></div>';
        var btn = wrap.querySelector('.avatar-picker-remove');
        if (btn) btn.remove();
    }

    var existingRemove = wrap.querySelector('.avatar-picker-remove');
    if (existingRemove) existingRemove.addEventListener('click', removeImage);
})();
</script>
