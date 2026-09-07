<?php
/**
 * Jodit editor init with media integration — rendered by MediaService::joditInit().
 *
 * @var string $selector CSS selector for the textarea
 * @var string $joditId  Unique ID for this instance
 * @var array  $config   Merged Jodit config (height, buttons)
 * @var string $adminBase Admin URL base for this plugin's admin routes
 */
?>

<div id="<?= $joditId ?>-offcanvas" class="offcanvas offcanvas-end" data-jodit-media="1" tabindex="-1" style="width:450px;">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Media Library</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div id="<?= $joditId ?>-upload-zone" class="border border-dashed rounded p-3 text-center mb-3" style="cursor:pointer;">
            <i class="ti ti-cloud-upload" style="font-size:1.5rem;"></i>
            <p class="mb-0 mt-1 small">Drop file or click to upload</p>
            <input type="file" class="d-none"
                   accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,video/quicktime">
        </div>
        <div class="row row-cols-3 g-2" id="<?= $joditId ?>-grid">
            <div class="text-center text-secondary py-4 w-100">Loading...</div>
        </div>
        <div class="text-center mt-3" id="<?= $joditId ?>-load-more" style="display:none;">
            <button type="button" class="btn btn-outline-secondary btn-sm">Load more</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
(function() {
    var csrfToken    = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var adminBase    = '<?= $adminBase ?>';
    var offcanvasEl  = document.getElementById('<?= $joditId ?>-offcanvas');
    var grid         = document.getElementById('<?= $joditId ?>-grid');
    var loadMoreWrap = document.getElementById('<?= $joditId ?>-load-more');
    var uploadZone   = document.getElementById('<?= $joditId ?>-upload-zone');
    var uploadInput  = uploadZone.querySelector('input[type="file"]');
    var page   = 1;
    var total   = 0;
    var loaded = false;

    if (offcanvasEl && offcanvasEl.parentElement !== document.body) {
        document.body.appendChild(offcanvasEl);
    }

    offcanvasEl.addEventListener('show.bs.offcanvas', function() {
        offcanvasEl.style.zIndex = '1085';
        setTimeout(function() {
            var backdrops = document.querySelectorAll('.offcanvas-backdrop');
            var backdrop = backdrops[backdrops.length - 1];
            if (backdrop) {
                backdrop.setAttribute('data-jodit-media-backdrop', '1');
                backdrop.style.zIndex = '1080';
            }
        }, 0);
    });

    offcanvasEl.addEventListener('hidden.bs.offcanvas', function() {
        offcanvasEl.style.zIndex = '';
        var activeModal = document.querySelector('.modal.show');
        if (activeModal) {
            activeModal.focus();
        }
    });

    uploadZone.addEventListener('click', function(e) {
        if (e.target.closest('input')) return;
        uploadInput.click();
    });
    uploadZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadZone.classList.add('border-primary');
    });
    uploadZone.addEventListener('dragleave', function() {
        uploadZone.classList.remove('border-primary');
    });
    uploadZone.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadZone.classList.remove('border-primary');
        if (e.dataTransfer.files.length) uploadFile(e.dataTransfer.files[0]);
    });
    uploadInput.addEventListener('change', function() {
        if (uploadInput.files.length) uploadFile(uploadInput.files[0]);
        uploadInput.value = '';
    });

    function uploadFile(file) {
        var isVideo  = file.type.startsWith('video/');
        var endpoint = isVideo ? adminBase + '/upload/video' : adminBase + '/upload/image';

        var fd = new FormData();
        fd.append('file', file);
        fd.append('_csrf_token', csrfToken);

        fetch(endpoint, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error) { alert(data.error); return; }
                page = 1;
                loaded = true;
                loadMedia(1);
            })
            .catch(function() { alert('Upload failed.'); });
    }

    function loadMedia(pg) {
        fetch(adminBase + '/json?page=' + pg)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (pg === 1) grid.innerHTML = '';
                total = data.total;

                if (data.items.length === 0 && pg === 1) {
                    grid.innerHTML = '<div class="text-center text-secondary py-4 w-100">No media found.</div>';
                    loadMoreWrap.style.display = 'none';
                    return;
                }

                data.items.forEach(function(item) {
                    var thumb = '';

                    if (item.type === 'image' && item.thumb_url) {
                        thumb = '<img src="' + item.thumb_url + '" class="card-img-top" loading="lazy"'
                              + ' style="height:90px; object-fit:cover;"'
                              + ' alt="' + (item.filename || '') + '">';
                    } else if (item.type === 'video') {
                        if (item.poster_url) {
                            thumb = '<img src="' + item.poster_url + '" class="card-img-top" loading="lazy"'
                                  + ' style="height:90px; object-fit:cover;"'
                                  + ' alt="' + (item.filename || '') + '">';
                        } else {
                            thumb = '<div class="card-img-top bg-dark d-flex align-items-center justify-content-center" style="height:90px;">'
                                  + '<i class="ti ti-video text-white" style="font-size:1.5rem;"></i></div>';
                        }
                    } else {
                        return;
                    }

                    var col = document.createElement('div');
                    col.className = 'col';
                    if (item.type === 'image') {
                        col.innerHTML = '<div class="card card-sm jodit-media-item"'
                            + ' data-type="' + item.type + '"'
                            + ' data-url="' + (item.medium_url || item.url || '') + '"'
                            + ' data-medium-url="' + (item.medium_url || item.url || '') + '"'
                            + ' data-thumb-url="' + (item.thumb_url || item.url || '') + '"'
                            + ' data-original="' + (item.url || '') + '"'
                            + ' data-poster="' + (item.poster_url || '') + '"'
                            + ' data-alt="' + (item.alt_text || '') + '">'
                            + thumb
                            + '<div class="card-body p-2">'
                            + '<div class="btn-group btn-group-sm w-100" role="group">'
                            + '<button type="button" class="btn btn-outline-secondary jodit-size-choice" data-size="small">S</button>'
                            + '<button type="button" class="btn btn-outline-secondary jodit-size-choice" data-size="medium">M</button>'
                            + '<button type="button" class="btn btn-outline-secondary jodit-size-choice" data-size="large">L</button>'
                            + '</div></div></div>';
                    }
                    grid.appendChild(col);
                });

                var totalLoaded = pg * data.per_page;
                loadMoreWrap.style.display = totalLoaded < total ? '' : 'none';
            });
    }

    offcanvasEl.addEventListener('show.bs.offcanvas', function() {
        if (!loaded) {
            loadMedia(1);
            loaded = true;
        }
    });

    loadMoreWrap.querySelector('button').addEventListener('click', function() {
        page++;
        loadMedia(page);
    });

    if (typeof Jodit === 'undefined') return;

    var editor = Jodit.make('<?= $selector ?>', {
        height: <?= (int) $config['height'] ?>,
        buttons: '<?= $config['buttons'] ?>',
        disablePlugins: 'beautify,ace',
        filebrowser: { isDisabled: true },
        controls: {
            image: {
                tooltip: 'Media Library',
                exec: function() {
                    new bootstrap.Offcanvas(offcanvasEl).show();
                }
            }
        },
        uploader: {
            url: adminBase + '/upload/image',
            filesVariableName: 'file',
            headers: { 'X-CSRF-Token': csrfToken },
            data: { '_csrf_token': csrfToken },
            isSuccess: function(resp) { return !resp.error; },
            process: function(resp) {
                return {
                    files: [resp.medium_url || resp.url],
                    path: '',
                    baseurl: '',
                    error: resp.error ? 1 : 0,
                    msg: resp.error || ''
                };
            },
            defaultHandlerSuccess: function(data) {
                if (data.files && data.files.length) {
                    editor.s.insertImage(data.files[0]);
                }
            }
        }
    });

    grid.addEventListener('click', function(e) {
        var choice = e.target.closest('.jodit-size-choice');
        var item = e.target.closest('.jodit-media-item');
        if (!item) return;

        var type     = item.dataset.type;
        var size     = choice ? choice.dataset.size : 'large';
        var url      = size === 'small'
            ? (item.dataset.thumbUrl || item.dataset.url)
            : (size === 'large' ? item.dataset.original : (item.dataset.mediumUrl || item.dataset.url));
        var original = item.dataset.original;
        var alt      = item.dataset.alt;
        var poster   = item.dataset.poster;

        if (type === 'image') {
            editor.s.insertHTML('<img src="' + url + '" alt="' + alt + '">');
        } else if (type === 'video') {
            var html = '<video controls src="' + original + '"';
            if (poster) html += ' poster="' + poster + '"';
            html += '></video>';
            editor.s.insertHTML(html);
        }

        bootstrap.Offcanvas.getInstance(offcanvasEl)?.hide();
    });

    function applyJoditDarkMode() {
        var iframe = editor.container.querySelector('.jodit-workplace iframe');
        if (!iframe || !iframe.contentDocument || !iframe.contentDocument.body) return;
        var isDark = document.body.getAttribute('data-bs-theme') === 'dark';
        iframe.contentDocument.body.style.backgroundColor = isDark ? '#0f172a' : '';
        iframe.contentDocument.body.style.color = isDark ? '#e2e8f0' : '';
    }

    setTimeout(applyJoditDarkMode, 500);
    new MutationObserver(applyJoditDarkMode).observe(document.body, { attributes: true, attributeFilter: ['data-bs-theme'] });
})();
});
</script>
