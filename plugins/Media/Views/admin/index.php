<?php
/**
 * Media library — admin page.
 *
 * @var string                         $pageTitle
 * @var string                         $adminBase
 * @var \Pubvana\Plugins\Media\Models\Media[] $media
 * @var int                            $total
 * @var int                            $page
 * @var int                            $perPage
 * @var string|null                    $typeFilter
 */
?>

<div id="media-library">

<!-- Upload zone -->
<div class="card mb-3">
    <div class="card-body">
        <div id="media-upload-zone" class="border border-dashed rounded p-4 text-center" style="cursor:pointer;">
            <i class="ti ti-cloud-upload" style="font-size:2rem;"></i>
            <p class="mb-0 mt-2">Drop files here or click to upload</p>
            <input type="file" id="media-file-input" class="d-none" multiple
                   accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,video/quicktime">
        </div>
    </div>
</div>

<!-- Filter tabs -->
<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item">
                <a class="nav-link <?= $typeFilter === null ? 'active' : '' ?>"
                   href="<?= $adminBase ?>">All</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $typeFilter === 'image' ? 'active' : '' ?>"
                   href="<?= $adminBase ?>?type=image">Images</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $typeFilter === 'video' ? 'active' : '' ?>"
                   href="<?= $adminBase ?>?type=video">Videos</a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <?php if (empty($media)): ?>
            <p class="text-secondary text-center py-4">No media found.</p>
        <?php else: ?>
            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3" id="media-grid">
                <?php foreach ($media as $item): ?>
                    <div class="col" data-media-id="<?= (int) $item->id ?>">
                        <div class="card card-sm media-card" role="button"
                             data-media='<?= htmlspecialchars(json_encode([
                                 'id'             => (int) $item->id,
                                 'type'           => $item->type,
                                 'filename'       => $item->filename,
                                 'path'           => $item->path,
                                 'alt_text'       => $item->alt_text,
                                 'title'          => $item->title,
                                 'embed_url'      => $item->embed_url,
                                 'embed_provider' => $item->embed_provider,
                                 'poster_path'    => $item->poster_path,
                             ]), ENT_QUOTES) ?>'>
                            <?php if ($item->type === 'image' && $item->path): ?>
                                <?php
                                    $dir  = dirname($item->path);
                                    $name = pathinfo($item->path, PATHINFO_FILENAME);
                                    $thumbUrl = '/' . $dir . '/thumbs/' . $name . '.webp';
                                ?>
                                <img src="<?= htmlspecialchars($thumbUrl) ?>"
                                     alt="<?= htmlspecialchars($item->alt_text ?? '') ?>"
                                     class="card-img-top" loading="lazy">
                            <?php elseif ($item->type === 'video'): ?>
                                <?php if ($item->poster_path): ?>
                                    <img src="/<?= htmlspecialchars($item->poster_path) ?>"
                                         alt="<?= htmlspecialchars($item->title ?? 'Video') ?>"
                                         class="card-img-top" loading="lazy">
                                <?php else: ?>
                                    <div class="card-img-top bg-dark d-flex align-items-center justify-content-center" style="height:120px;">
                                        <i class="ti ti-video text-white" style="font-size:2rem;"></i>
                                    </div>
                                <?php endif; ?>
                            <?php elseif ($item->type === 'embed'): ?>
                                <div class="card-img-top bg-azure-lt d-flex align-items-center justify-content-center" style="height:120px;">
                                    <i class="ti ti-brand-<?= htmlspecialchars($item->embed_provider ?? 'youtube') ?>" style="font-size:2rem;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body p-2">
                                <small class="text-truncate d-block text-secondary">
                                    <?= htmlspecialchars($item->filename) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php $totalPages = (int) ceil($total / $perPage); ?>
            <?php if ($totalPages > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination justify-content-center">
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                <a class="page-link"
                                   href="<?= $adminBase ?>?page=<?= $p ?><?= $typeFilter ? '&type=' . $typeFilter : '' ?>">
                                    <?= $p ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Detail sidebar -->
<div id="media-detail" class="offcanvas offcanvas-end" tabindex="-1">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Media Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body" id="media-detail-body">
    </div>
</div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var adminBase = '<?= $adminBase ?>';
    var grid = document.getElementById('media-grid');
    var uploadZone = document.getElementById('media-upload-zone');
    var fileInput = document.getElementById('media-file-input');

    if (uploadZone) {
        uploadZone.addEventListener('click', function () { fileInput.click(); });

        uploadZone.addEventListener('dragover', function (e) {
            e.preventDefault();
            uploadZone.classList.add('border-primary');
        });

        uploadZone.addEventListener('dragleave', function () {
            uploadZone.classList.remove('border-primary');
        });

        uploadZone.addEventListener('drop', function (e) {
            e.preventDefault();
            uploadZone.classList.remove('border-primary');
            handleFiles(e.dataTransfer.files);
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            handleFiles(fileInput.files);
            fileInput.value = '';
        });
    }

    function handleFiles(files) {
        var fileArray = Array.from(files);
        var completed = 0;

        fileArray.forEach(function (file) {
            var isVideo = file.type.startsWith('video/');
            var endpoint = isVideo ? adminBase + '/upload/video' : adminBase + '/upload/image';

            var fd = new FormData();
            fd.append('file', file);
            fd.append('_csrf_token', csrfToken);

            fetch(endpoint, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.error) { alert(data.error); return; }
                    completed++;

                    if (completed === fileArray.length) {
                        if (fileArray.length === 1 && data.type === 'image') {
                            window.location.href = adminBase + '/' + data.id + '/editor';
                        } else {
                            location.reload();
                        }
                    }
                })
                .catch(function () { alert('Upload failed.'); });
        });
    }

    if (grid) {
        grid.addEventListener('click', function (e) {
            var card = e.target.closest('.media-card');
            if (!card) return;

            var media = JSON.parse(card.dataset.media);
            showDetail(media);
        });
    }

    function showDetail(media) {
        var body = document.getElementById('media-detail-body');
        var preview = '';

        if (media.type === 'image' && media.path) {
            preview = '<img src="/' + media.path + '" class="img-fluid rounded mb-3" alt="">';
        } else if (media.type === 'video' && media.poster_path) {
            preview = '<img src="/' + media.poster_path + '" class="img-fluid rounded mb-3" alt="">';
        } else if (media.type === 'video') {
            preview = '<div class="bg-dark rounded d-flex align-items-center justify-content-center mb-3" style="height:200px;">'
                + '<i class="ti ti-video text-white" style="font-size:3rem;"></i></div>';
        } else if (media.type === 'embed') {
            preview = '<div class="bg-azure-lt rounded d-flex align-items-center justify-content-center mb-3" style="height:200px;">'
                + '<i class="ti ti-brand-' + (media.embed_provider || 'youtube') + '" style="font-size:3rem;"></i></div>';
        }

        body.innerHTML = preview
            + '<div class="mb-3"><label class="form-label">Alt Text</label>'
            + '<input type="text" class="form-control" id="detail-alt" value="' + (media.alt_text || '') + '"></div>'
            + '<div class="mb-3"><label class="form-label">Title</label>'
            + '<input type="text" class="form-control" id="detail-title" value="' + (media.title || '') + '"></div>'
            + '<div class="mb-3"><small class="text-secondary">' + media.filename + '</small></div>'
            + (media.type === 'image'
                ? '<div class="mb-3"><a href="' + adminBase + '/' + media.id + '/editor" class="btn btn-outline-primary w-100"><i class="ti ti-photo-edit"></i> Edit Image</a></div>'
                : '')
            + (media.type === 'video'
                ? '<div class="mb-3"><label class="form-label">Upload Poster</label>'
                  + '<input type="file" class="form-control" id="detail-poster" accept="image/*"></div>'
                : '')
            + '<div class="d-flex gap-2">'
            + '<button class="btn btn-primary" id="detail-save" data-id="' + media.id + '">Save</button>'
            + '<button class="btn btn-outline-danger" id="detail-delete" data-id="' + media.id + '">Delete</button></div>';

        document.getElementById('detail-save').addEventListener('click', function () {
            var id = this.dataset.id;
            var fd = new FormData();
            fd.append('alt_text', document.getElementById('detail-alt').value);
            fd.append('title', document.getElementById('detail-title').value);
            fd.append('_csrf_token', csrfToken);

            fetch(adminBase + '/' + id + '/update', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.error) { alert(data.error); }
                });
        });

        document.getElementById('detail-delete').addEventListener('click', function () {
            if (!confirm('Delete this media? This cannot be undone.')) return;
            var id = this.dataset.id;
            var fd = new FormData();
            fd.append('_csrf_token', csrfToken);

            fetch(adminBase + '/' + id + '/delete', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.error) { alert(data.error); return; }
                    var el = document.querySelector('[data-media-id="' + id + '"]');
                    if (el) el.remove();
                    var offcanvas = document.getElementById('media-detail');
                    if (offcanvas) {
                        var instance = bootstrap.Offcanvas.getInstance(offcanvas);
                        if (instance) instance.hide();
                    }
                });
        });

        var posterInput = document.getElementById('detail-poster');
        if (posterInput) {
            posterInput.addEventListener('change', function () {
                var fd = new FormData();
                fd.append('file', this.files[0]);
                fd.append('_csrf_token', csrfToken);

                fetch(adminBase + '/' + media.id + '/poster', { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.error) { alert(data.error); return; }
                        location.reload();
                    });
            });
        }

        var offcanvasEl = document.getElementById('media-detail');
        if (offcanvasEl) {
            var bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl) || new bootstrap.Offcanvas(offcanvasEl);
            bsOffcanvas.show();
        }
    }
});
</script>
