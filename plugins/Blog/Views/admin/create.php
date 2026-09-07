<?php
/**
 * Create post - admin form.
 *
 * @var string                                     $pageTitle
 * @var string                                     $adminBase
 * @var \Pubvana\Plugins\Blog\Models\Category[]    $categories
 */
?>

<div class="d-flex align-items-center justify-content-end mb-4">
    <a href="<?= $adminBase ?>" class="btn btn-outline-secondary">Back</a>
</div>

<form method="POST" action="<?= $adminBase ?>/store">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="title">Title</label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="slug">Slug</label>
                        <input type="text" name="slug" id="slug" class="form-control"
                               placeholder="Auto-generated from title">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea name="content" id="content" class="form-control" rows="20"></textarea>
                    </div>
                    <?php if (!empty($joditHtml)): ?>
                        <?= $joditHtml ?>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label" for="excerpt">Excerpt</label>
                        <textarea name="excerpt" id="excerpt" class="form-control" rows="3"
                                  placeholder="Optional short summary"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Publish</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="status">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="draft" selected>Draft</option>
                            <option value="published">Published</option>
                            <option value="scheduled">Scheduled</option>
                        </select>
                    </div>
                    <div class="mb-3" id="scheduled-date-wrapper" style="display:none;">
                        <label class="form-label" for="published_at">Publish Date</label>
                        <input type="datetime-local" name="published_at" id="published_at" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" name="is_featured" value="1" class="form-check-input">
                            <span class="form-check-label">Featured Post</span>
                        </label>
                    </div>
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" name="allow_comments" value="1" class="form-check-input" checked>
                            <span class="form-check-label">Allow Comments</span>
                        </label>
                        <div class="text-secondary small fst-italic">Requires Global Comments to be enabled in Comment Settings.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" name="purify_content" value="1" class="form-check-input" checked>
                            <span class="form-check-label">Sanitize HTML</span>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Create Post</button>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Featured Image</h3>
                </div>
                <div class="card-body">
                    <input type="hidden" name="featured_image" id="featured-image-path" value="">
                    <input type="hidden" name="media_id" id="featured-image-media-id" value="0">
                    <div id="featured-image-preview" class="mb-2">
                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height:150px;">
                            <i class="ti ti-photo text-secondary" style="font-size:2rem;"></i>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm flex-fill" id="featured-image-choose">
                            <i class="ti ti-photo"></i> Library
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" id="featured-image-remove" style="display:none;">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Categories</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($categories)): ?>
                        <p class="text-secondary mb-0">No categories. <a href="<?= $adminBase ?>/categories/create">Create one</a>.</p>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <label class="form-check">
                                <input type="checkbox" name="categories[]" value="<?= (int) $cat->id ?>" class="form-check-input">
                                <span class="form-check-label"><?= htmlspecialchars($cat->name) ?></span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Tags</h3>
                </div>
                <div class="card-body">
                    <input type="text" name="tags_raw" class="form-control"
                           placeholder="Comma separated tags">
                </div>
            </div>

            <?php foreach (\Flight::app()->adext()->get('content.edit.panel', 'default', ['content_type' => 'post', 'content_id' => 0]) as $panel): ?>
                <?= $panel['output'] ?? '' ?>
            <?php endforeach; ?>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var titleEl = document.getElementById('title');
    var slugEl  = document.getElementById('slug');
    var slugEdited = false;

    slugEl.addEventListener('input', function () {
        slugEdited = this.value !== '';
    });

    titleEl.addEventListener('input', function () {
        if (slugEdited) return;
        slugEl.value = this.value.toLowerCase().trim()
            .replace(/&/g, 'and')
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/[\s]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    });

    var statusEl = document.getElementById('status');
    var dateWrap = document.getElementById('scheduled-date-wrapper');
    statusEl.addEventListener('change', function () {
        dateWrap.style.display = this.value === 'scheduled' ? '' : 'none';
    });
});
</script>

<!-- Featured Image Media Picker -->
<div id="featured-image-offcanvas" class="offcanvas offcanvas-end" tabindex="-1" style="width:450px;">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Choose Featured Image</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div id="fi-upload-zone" class="border border-dashed rounded p-3 text-center mb-3" style="cursor:pointer;">
            <i class="ti ti-cloud-upload" style="font-size:1.5rem;"></i>
            <p class="mb-0 mt-1 small">Drop image or click to upload</p>
            <input type="file" class="d-none" accept="image/jpeg,image/png,image/gif,image/webp">
        </div>
        <div class="row row-cols-3 g-2" id="fi-grid">
            <div class="text-center text-secondary py-4 w-100">Loading...</div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var csrfToken   = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var pathInput   = document.getElementById('featured-image-path');
    var mediaIdInput = document.getElementById('featured-image-media-id');
    var preview     = document.getElementById('featured-image-preview');
    var chooseBtn   = document.getElementById('featured-image-choose');
    var removeBtn   = document.getElementById('featured-image-remove');
    var offcanvasEl = document.getElementById('featured-image-offcanvas');
    var grid        = document.getElementById('fi-grid');
    var uploadZone  = document.getElementById('fi-upload-zone');
    var uploadInput = uploadZone.querySelector('input[type="file"]');
    var loaded      = false;

    function selectImage(url, mediaId) {
        pathInput.value = url;
        mediaIdInput.value = mediaId || 0;
        preview.innerHTML = '<img src="' + url + '" class="img-fluid rounded" alt="">';
        removeBtn.style.display = '';
        bootstrap.Offcanvas.getInstance(offcanvasEl)?.hide();
    }

    chooseBtn.addEventListener('click', function () {
        new bootstrap.Offcanvas(offcanvasEl).show();
    });

    removeBtn.addEventListener('click', function () {
        pathInput.value = '';
        mediaIdInput.value = '0';
        preview.innerHTML = '<div class="bg-light rounded d-flex align-items-center justify-content-center" style="height:150px;"><i class="ti ti-photo text-secondary" style="font-size:2rem;"></i></div>';
        removeBtn.style.display = 'none';
    });

    uploadZone.addEventListener('click', function (e) {
        if (e.target.closest('input')) return;
        uploadInput.click();
    });
    uploadZone.addEventListener('dragover', function (e) { e.preventDefault(); uploadZone.classList.add('border-primary'); });
    uploadZone.addEventListener('dragleave', function () { uploadZone.classList.remove('border-primary'); });
    uploadZone.addEventListener('drop', function (e) {
        e.preventDefault();
        uploadZone.classList.remove('border-primary');
        if (e.dataTransfer.files.length) uploadFile(e.dataTransfer.files[0]);
    });
    uploadInput.addEventListener('change', function () {
        if (uploadInput.files.length) uploadFile(uploadInput.files[0]);
        uploadInput.value = '';
    });

    function uploadFile(file) {
        var fd = new FormData();
        fd.append('file', file);
        fd.append('_csrf_token', csrfToken);
        fetch('/admin/media/upload/image', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) { alert(data.error); return; }
                selectImage(data.medium_url || data.url, data.id);
                loadMedia(1);
            })
            .catch(function () { alert('Upload failed.'); });
    }

    function loadMedia(pg) {
        fetch('/admin/media/json?type=image&page=' + pg)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (pg === 1) grid.innerHTML = '';
                if (data.items.length === 0 && pg === 1) {
                    grid.innerHTML = '<div class="text-center text-secondary py-4 w-100">No images found.</div>';
                    return;
                }
                data.items.forEach(function (item) {
                    if (item.type !== 'image') return;
                    var col = document.createElement('div');
                    col.className = 'col';
                    col.innerHTML = '<div class="card card-sm" role="button" data-url="' + (item.medium_url || item.url || '') + '" data-id="' + item.id + '">'
                        + '<img src="' + (item.thumb_url || item.url || '') + '" class="card-img-top" loading="lazy" style="height:90px; object-fit:cover;" alt="">'
                        + '<div class="card-body p-1"><small class="text-truncate d-block text-secondary">' + (item.filename || '') + '</small></div></div>';
                    grid.appendChild(col);
                });
            });
    }

    offcanvasEl.addEventListener('show.bs.offcanvas', function () {
        if (!loaded) { loadMedia(1); loaded = true; }
    });

    grid.addEventListener('click', function (e) {
        var card = e.target.closest('.card');
        if (!card || !card.dataset.url) return;
        selectImage(card.dataset.url, card.dataset.id);
    });
});
</script>
