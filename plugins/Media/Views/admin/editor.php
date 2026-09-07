<?php
/**
 * Image editor — admin page.
 *
 * @var string                       $pageTitle
 * @var string                       $adminBase
 * @var \Pubvana\Plugins\Media\Models\Media $media
 * @var array{width: int, height: int, mime: string} $info
 * @var string[]                     $capabilities
 */

$mediaId   = (int) $media->id;
$mediaPath = $media->path;
$sizeBytes = (int) $media->size;
$sizeStr   = $sizeBytes < 1024 * 1024
    ? round($sizeBytes / 1024) . ' KB'
    : round($sizeBytes / 1024 / 1024, 1) . ' MB';
$formatStr = strtoupper(str_replace('image/', '', $info['mime']));

$has = fn(string $cap): bool => in_array($cap, $capabilities, true);
$btn = fn(string $cap): string => $has($cap) ? '' : ' disabled';
?>

<div class="mb-3">
    <a href="<?= $adminBase ?>" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-arrow-left"></i> Back to Library
    </a>
</div>

<div class="row g-4">
    <!-- Image preview -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-3">
                <div id="editor-container"
                     style="position:relative; display:inline-block; max-width:100%;"
                     data-media-id="<?= $mediaId ?>"
                     data-media-path="<?= htmlspecialchars($mediaPath) ?>"
                     data-natural-w="<?= $info['width'] ?>"
                     data-natural-h="<?= $info['height'] ?>">
                    <img id="editor-image"
                         src="/<?= htmlspecialchars($mediaPath) ?>?t=<?= time() ?>"
                         alt=""
                         style="display:block; max-width:100%; max-height:65vh; user-select:none;"
                         draggable="false">
                </div>
            </div>
        </div>
    </div>

    <!-- Controls -->
    <div class="col-lg-4">
        <!-- Operations -->
        <div class="card mb-3">
            <div class="card-header"><h4 class="card-title mb-0">Operations</h4></div>
            <div class="card-body">
                <!-- Crop -->
                <div class="mb-3">
                    <button class="btn btn-sm btn-outline-primary w-100" id="btn-crop"<?= $btn('crop') ?>>
                        <i class="ti ti-crop"></i> Crop
                    </button>
                    <div id="crop-controls" class="mt-2 d-none">
                        <div class="row g-1 mb-2">
                            <div class="col-3">
                                <label class="form-label mb-0" style="font-size:.75rem;">X</label>
                                <input type="number" id="crop-x" class="form-control form-control-sm" value="0" min="0">
                            </div>
                            <div class="col-3">
                                <label class="form-label mb-0" style="font-size:.75rem;">Y</label>
                                <input type="number" id="crop-y" class="form-control form-control-sm" value="0" min="0">
                            </div>
                            <div class="col-3">
                                <label class="form-label mb-0" style="font-size:.75rem;">W</label>
                                <input type="number" id="crop-w" class="form-control form-control-sm" value="0" min="1">
                            </div>
                            <div class="col-3">
                                <label class="form-label mb-0" style="font-size:.75rem;">H</label>
                                <input type="number" id="crop-h" class="form-control form-control-sm" value="0" min="1">
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" id="crop-apply">Apply</button>
                            <button class="btn btn-sm btn-outline-secondary" id="crop-cancel">Cancel</button>
                        </div>
                    </div>
                </div>

                <hr class="my-2">

                <!-- Rotate / Flip -->
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <button class="btn btn-sm btn-outline-primary" data-op="rotate" data-params='{"degrees":270}'<?= $btn('rotate') ?> title="Rotate Left">
                        <i class="ti ti-rotate-2"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-primary" data-op="rotate" data-params='{"degrees":90}'<?= $btn('rotate') ?> title="Rotate Right">
                        <i class="ti ti-rotate-clockwise-2"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-primary" data-op="flip" data-params='{"direction":"horizontal"}'<?= $btn('flip') ?> title="Flip Horizontal">
                        <i class="ti ti-flip-horizontal"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-primary" data-op="flip" data-params='{"direction":"vertical"}'<?= $btn('flip') ?> title="Flip Vertical">
                        <i class="ti ti-flip-vertical"></i>
                    </button>
                </div>

                <!-- One-click operations -->
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <button class="btn btn-sm btn-outline-primary" data-op="sharpen" data-params='{}'<?= $btn('sharpen') ?>>
                        <i class="ti ti-focus-2"></i> Sharpen
                    </button>
                    <button class="btn btn-sm btn-outline-primary" data-op="auto_orient" data-params='{}'<?= $btn('auto_orient') ?>>
                        <i class="ti ti-compass"></i> Auto-Orient
                    </button>
                    <button class="btn btn-sm btn-outline-primary" data-op="strip_exif" data-params='{}'<?= $btn('strip_exif') ?>>
                        <i class="ti ti-eraser"></i> Strip EXIF
                    </button>
                </div>

                <hr class="my-2">

                <!-- Brightness -->
                <div class="mb-3">
                    <label class="form-label mb-1">Brightness: <span id="brightness-val">0</span></label>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="range" class="form-range flex-grow-1" id="brightness-range"
                               min="-100" max="100" value="0" step="5"<?= $btn('brightness') ?>>
                        <button class="btn btn-sm btn-outline-primary" id="brightness-apply"<?= $btn('brightness') ?>>Apply</button>
                    </div>
                </div>

                <!-- Contrast -->
                <div class="mb-3">
                    <label class="form-label mb-1">Contrast: <span id="contrast-val">0</span></label>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="range" class="form-range flex-grow-1" id="contrast-range"
                               min="-100" max="100" value="0" step="5"<?= $btn('contrast') ?>>
                        <button class="btn btn-sm btn-outline-primary" id="contrast-apply"<?= $btn('contrast') ?>>Apply</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- EXIF Data -->
        <div class="card mb-3">
            <div class="card-header" style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#exif-collapse">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">EXIF Data</h4>
                    <i class="ti ti-chevron-down"></i>
                </div>
            </div>
            <div id="exif-collapse" class="collapse">
                <div class="card-body p-0" id="exif-body">
                    <?php if (empty($exifData)): ?>
                        <p class="text-secondary p-3 mb-0">No EXIF data.</p>
                    <?php else: ?>
                        <table class="table table-sm mb-0">
                            <tbody>
                            <?php foreach ($exifData as $key => $value): ?>
                                <tr>
                                    <td class="text-secondary ps-3" style="white-space:nowrap;"><?= htmlspecialchars($key) ?></td>
                                    <td style="word-break:break-all;"><?= htmlspecialchars($value) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Info & Revert -->
        <div class="card">
            <div class="card-body">
                <div class="mb-2">
                    <span id="info-dimensions"><?= $info['width'] ?> &times; <?= $info['height'] ?></span> px
                </div>
                <div class="mb-2">
                    <span id="info-format"><?= htmlspecialchars($formatStr) ?></span>
                    &middot; <span id="info-size"><?= $sizeStr ?></span>
                </div>
                <div class="mb-3">
                    <small class="text-secondary"><?= htmlspecialchars($media->filename) ?></small>
                </div>
                <button class="btn btn-outline-warning w-100" id="btn-revert">
                    <i class="ti ti-restore"></i> Revert to Original
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.crop-selection {
    position: absolute;
    border: 2px dashed #fff;
    box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
    cursor: move;
    z-index: 10;
    min-width: 10px;
    min-height: 10px;
}
.crop-handle {
    position: absolute;
    width: 10px;
    height: 10px;
    background: #fff;
    border: 1px solid #333;
    z-index: 11;
}
.crop-handle-nw { top: -5px; left: -5px; cursor: nw-resize; }
.crop-handle-ne { top: -5px; right: -5px; cursor: ne-resize; }
.crop-handle-sw { bottom: -5px; left: -5px; cursor: sw-resize; }
.crop-handle-se { bottom: -5px; right: -5px; cursor: se-resize; }
#editor-container.crop-mode { cursor: crosshair; overflow: hidden; }
.editor-busy { opacity: 0.5; pointer-events: none; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var container = document.getElementById('editor-container');
    if (!container) return;

    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var adminBase = '<?= $adminBase ?>';
    var mediaId   = parseInt(container.dataset.mediaId, 10);
    var imageEl   = document.getElementById('editor-image');
    var mediaPath = container.dataset.mediaPath;
    var naturalW  = parseInt(container.dataset.naturalW, 10);
    var naturalH  = parseInt(container.dataset.naturalH, 10);

    function escHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function updateExif(exifData) {
        var body = document.getElementById('exif-body');
        if (!exifData || Object.keys(exifData).length === 0) {
            body.innerHTML = '<p class="text-secondary p-3 mb-0">No EXIF data.</p>';
            return;
        }
        var html = '<table class="table table-sm mb-0"><tbody>';
        var keys = Object.keys(exifData).sort();
        for (var i = 0; i < keys.length; i++) {
            html += '<tr><td class="text-secondary ps-3" style="white-space:nowrap;">'
                  + escHtml(keys[i]) + '</td><td style="word-break:break-all;">'
                  + escHtml(exifData[keys[i]]) + '</td></tr>';
        }
        html += '</tbody></table>';
        body.innerHTML = html;
    }

    function setBusy(on) {
        container.classList.toggle('editor-busy', on);
    }

    function refreshImage() {
        imageEl.src = '/' + mediaPath + '?t=' + Date.now();
    }

    function updateInfo(data) {
        if (data.info) {
            naturalW = data.info.width;
            naturalH = data.info.height;
            document.getElementById('info-dimensions').textContent = data.info.width + ' \u00d7 ' + data.info.height;
            var mime = (data.info.mime || '').replace('image/', '').toUpperCase();
            document.getElementById('info-format').textContent = mime;
        }
        if (data.size) {
            var bytes = parseInt(data.size);
            var str = bytes < 1024 * 1024
                ? Math.round(bytes / 1024) + ' KB'
                : (bytes / 1024 / 1024).toFixed(1) + ' MB';
            document.getElementById('info-size').textContent = str;
        }
    }

    function sendEdit(operation, params) {
        setBusy(true);
        var fd = new FormData();
        fd.append('operation', operation);
        fd.append('params', JSON.stringify(params));
        fd.append('_csrf_token', csrfToken);

        return fetch(adminBase + '/' + mediaId + '/edit', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                setBusy(false);
                if (data.error) { alert(data.error); return null; }
                refreshImage();
                updateInfo(data);
                if (data.exif !== undefined) updateExif(data.exif);
                return data;
            })
            .catch(function () {
                setBusy(false);
                alert('Operation failed.');
                return null;
            });
    }

    document.querySelectorAll('[data-op]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var op     = this.dataset.op;
            var params = JSON.parse(this.dataset.params || '{}');
            sendEdit(op, params);
        });
    });

    var brightnessRange = document.getElementById('brightness-range');
    var brightnessVal   = document.getElementById('brightness-val');
    var contrastRange   = document.getElementById('contrast-range');
    var contrastVal     = document.getElementById('contrast-val');

    brightnessRange.addEventListener('input', function () {
        brightnessVal.textContent = this.value;
    });
    contrastRange.addEventListener('input', function () {
        contrastVal.textContent = this.value;
    });

    document.getElementById('brightness-apply').addEventListener('click', function () {
        var level = parseInt(brightnessRange.value);
        if (level === 0) return;
        sendEdit('brightness', { level: level }).then(function () {
            brightnessRange.value = 0;
            brightnessVal.textContent = '0';
        });
    });

    document.getElementById('contrast-apply').addEventListener('click', function () {
        var level = parseInt(contrastRange.value);
        if (level === 0) return;
        sendEdit('contrast', { level: level }).then(function () {
            contrastRange.value = 0;
            contrastVal.textContent = '0';
        });
    });

    document.getElementById('btn-revert').addEventListener('click', function () {
        if (!confirm('Revert to the original image? All edits will be lost.')) return;
        setBusy(true);
        var fd = new FormData();
        fd.append('_csrf_token', csrfToken);

        fetch(adminBase + '/' + mediaId + '/revert', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                setBusy(false);
                if (data.error) { alert(data.error); return; }
                refreshImage();
                updateInfo(data);
                if (data.exif !== undefined) updateExif(data.exif);
            })
            .catch(function () {
                setBusy(false);
                alert('Revert failed.');
            });
    });

    var cropMode    = false;
    var cropBox     = null;
    var dragState   = null;
    var dragStart   = {};
    var activeHandle = null;

    var cropControls = document.getElementById('crop-controls');
    var cropXInput   = document.getElementById('crop-x');
    var cropYInput   = document.getElementById('crop-y');
    var cropWInput   = document.getElementById('crop-w');
    var cropHInput   = document.getElementById('crop-h');

    function getScale() {
        return { x: naturalW / imageEl.clientWidth, y: naturalH / imageEl.clientHeight };
    }

    function clamp(val, min, max) {
        return Math.max(min, Math.min(max, val));
    }

    function syncInputsFromBox() {
        if (!cropBox) return;
        var scale = getScale();
        cropXInput.value = Math.round(parseFloat(cropBox.style.left) * scale.x);
        cropYInput.value = Math.round(parseFloat(cropBox.style.top) * scale.y);
        cropWInput.value = Math.round(parseFloat(cropBox.style.width) * scale.x);
        cropHInput.value = Math.round(parseFloat(cropBox.style.height) * scale.y);
    }

    function syncBoxFromInputs() {
        if (!cropBox) return;
        var scale = getScale();
        cropBox.style.left   = (parseInt(cropXInput.value) || 0) / scale.x + 'px';
        cropBox.style.top    = (parseInt(cropYInput.value) || 0) / scale.y + 'px';
        cropBox.style.width  = (parseInt(cropWInput.value) || 0) / scale.x + 'px';
        cropBox.style.height = (parseInt(cropHInput.value) || 0) / scale.y + 'px';
    }

    function createCropBox(x, y, w, h) {
        removeCropBox();
        cropBox = document.createElement('div');
        cropBox.className = 'crop-selection';
        cropBox.style.left   = x + 'px';
        cropBox.style.top    = y + 'px';
        cropBox.style.width  = w + 'px';
        cropBox.style.height = h + 'px';

        ['nw', 'ne', 'sw', 'se'].forEach(function (pos) {
            var handle = document.createElement('div');
            handle.className = 'crop-handle crop-handle-' + pos;
            handle.dataset.handle = pos;
            cropBox.appendChild(handle);
        });

        container.appendChild(cropBox);
        syncInputsFromBox();
    }

    function removeCropBox() {
        if (cropBox) { cropBox.remove(); cropBox = null; }
    }

    document.getElementById('btn-crop').addEventListener('click', function () {
        if (cropMode) return;
        cropMode = true;
        container.classList.add('crop-mode');
        cropControls.classList.remove('d-none');
    });

    document.getElementById('crop-cancel').addEventListener('click', function () {
        cropMode = false;
        container.classList.remove('crop-mode');
        cropControls.classList.add('d-none');
        removeCropBox();
    });

    document.getElementById('crop-apply').addEventListener('click', function () {
        var x = parseInt(cropXInput.value) || 0;
        var y = parseInt(cropYInput.value) || 0;
        var w = parseInt(cropWInput.value) || 0;
        var h = parseInt(cropHInput.value) || 0;
        if (w < 1 || h < 1) { alert('Draw a crop region first.'); return; }

        sendEdit('crop', { x: x, y: y, width: w, height: h }).then(function (data) {
            if (data) {
                cropMode = false;
                container.classList.remove('crop-mode');
                cropControls.classList.add('d-none');
                removeCropBox();
            }
        });
    });

    [cropXInput, cropYInput, cropWInput, cropHInput].forEach(function (input) {
        input.addEventListener('change', syncBoxFromInputs);
    });

    container.addEventListener('mousedown', function (e) {
        if (!cropMode) return;
        var rect = imageEl.getBoundingClientRect();

        if (e.target.dataset.handle) {
            e.preventDefault();
            dragState = 'resizing';
            activeHandle = e.target.dataset.handle;
            dragStart = {
                mx: e.clientX, my: e.clientY,
                left: parseFloat(cropBox.style.left), top: parseFloat(cropBox.style.top),
                width: parseFloat(cropBox.style.width), height: parseFloat(cropBox.style.height)
            };
            return;
        }

        if (cropBox && e.target === cropBox) {
            e.preventDefault();
            dragState = 'moving';
            dragStart = { mx: e.clientX, my: e.clientY,
                left: parseFloat(cropBox.style.left), top: parseFloat(cropBox.style.top) };
            return;
        }

        if (e.target === imageEl) {
            e.preventDefault();
            dragState = 'drawing';
            dragStart = { x: e.clientX - rect.left, y: e.clientY - rect.top };
            createCropBox(dragStart.x, dragStart.y, 0, 0);
        }
    });

    document.addEventListener('mousemove', function (e) {
        if (!dragState || !cropBox) return;
        var rect = imageEl.getBoundingClientRect();
        var imgW = imageEl.clientWidth;
        var imgH = imageEl.clientHeight;

        if (dragState === 'drawing') {
            var cx = clamp(e.clientX - rect.left, 0, imgW);
            var cy = clamp(e.clientY - rect.top, 0, imgH);
            cropBox.style.left   = Math.min(dragStart.x, cx) + 'px';
            cropBox.style.top    = Math.min(dragStart.y, cy) + 'px';
            cropBox.style.width  = Math.abs(cx - dragStart.x) + 'px';
            cropBox.style.height = Math.abs(cy - dragStart.y) + 'px';
            syncInputsFromBox();
        }

        if (dragState === 'moving') {
            var newLeft = clamp(dragStart.left + (e.clientX - dragStart.mx), 0, imgW - parseFloat(cropBox.style.width));
            var newTop  = clamp(dragStart.top + (e.clientY - dragStart.my), 0, imgH - parseFloat(cropBox.style.height));
            cropBox.style.left = newLeft + 'px';
            cropBox.style.top  = newTop + 'px';
            syncInputsFromBox();
        }

        if (dragState === 'resizing') {
            var rdx = e.clientX - dragStart.mx;
            var rdy = e.clientY - dragStart.my;
            var s = dragStart;
            var newLeft, newTop, newW, newH;

            if (activeHandle === 'se') {
                newLeft = s.left; newTop = s.top;
                newW = clamp(s.width + rdx, 10, imgW - s.left);
                newH = clamp(s.height + rdy, 10, imgH - s.top);
            } else if (activeHandle === 'sw') {
                newW = clamp(s.width - rdx, 10, s.left + s.width);
                newH = clamp(s.height + rdy, 10, imgH - s.top);
                newLeft = s.left + s.width - newW; newTop = s.top;
            } else if (activeHandle === 'ne') {
                newLeft = s.left;
                newW = clamp(s.width + rdx, 10, imgW - s.left);
                newH = clamp(s.height - rdy, 10, s.top + s.height);
                newTop = s.top + s.height - newH;
            } else if (activeHandle === 'nw') {
                newW = clamp(s.width - rdx, 10, s.left + s.width);
                newH = clamp(s.height - rdy, 10, s.top + s.height);
                newLeft = s.left + s.width - newW;
                newTop = s.top + s.height - newH;
            }

            cropBox.style.left = newLeft + 'px';
            cropBox.style.top = newTop + 'px';
            cropBox.style.width = newW + 'px';
            cropBox.style.height = newH + 'px';
            syncInputsFromBox();
        }
    });

    document.addEventListener('mouseup', function () {
        dragState = null;
        activeHandle = null;
    });
});
</script>
