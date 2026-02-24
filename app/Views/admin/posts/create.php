<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">New Post</h1>
    <a href="<?= base_url('admin/posts') ?>" class="btn btn-sm btn-outline-secondary">Back to Posts</a>
</div>

<form method="POST" action="<?= base_url('admin/posts/create') ?>">
<?= csrf_field() ?>
<input type="hidden" name="content_type" id="content_type" value="html">

<div class="row">
    <div class="col-lg-8">

        <!-- Title -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="form-group">
                    <label class="font-weight-bold">Title *</label>
                    <input type="text" name="title" class="form-control form-control-lg" value="<?= esc(old('title')) ?>" required>
                </div>

                <!-- Editor Toggle -->
                <div class="form-group">
                    <label class="font-weight-bold d-block">Editor</label>
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="editor_type" id="et_html" value="html" checked autocomplete="off">
                        <label class="btn btn-outline-primary" for="et_html"><i class="fas fa-code"></i> HTML Editor</label>
                        <input type="radio" class="btn-check" name="editor_type" id="et_md" value="markdown" autocomplete="off">
                        <label class="btn btn-outline-secondary" for="et_md"><i class="fab fa-markdown"></i> Markdown</label>
                    </div>
                </div>

                <!-- HTML Editor -->
                <div id="editor-html">
                    <textarea name="content" id="content-html" class="form-control" rows="15"><?= esc(old('content')) ?></textarea>
                </div>
                <!-- Markdown Editor -->
                <div id="editor-md" style="display:none">
                    <textarea name="content_md" id="content-md" class="form-control" rows="15"><?= esc(old('content')) ?></textarea>
                </div>
            </div>
        </div>

        <!-- Excerpt -->
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Excerpt</h6></div>
            <div class="card-body">
                <textarea name="excerpt" class="form-control" rows="3" placeholder="Optional short summary..."><?= esc(old('excerpt')) ?></textarea>
            </div>
        </div>

        <!-- SEO -->
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">SEO</h6></div>
            <div class="card-body">
                <div class="form-group">
                    <label>Meta Title</label>
                    <input type="text" name="meta_title" class="form-control" value="<?= esc(old('meta_title')) ?>">
                </div>
                <div class="form-group">
                    <label>Meta Description</label>
                    <textarea name="meta_description" class="form-control" rows="2"><?= esc(old('meta_description')) ?></textarea>
                </div>
            </div>
        </div>

    </div>

    <div class="col-lg-4">

        <!-- Publish Settings -->
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Publish</h6></div>
            <div class="card-body">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="draft" <?= old('status') === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= old('status') === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="scheduled" <?= old('status') === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                    </select>
                </div>
                <div class="form-group" id="published-at-group" style="display:none">
                    <label>Scheduled Date &amp; Time</label>
                    <input type="datetime-local" name="published_at" class="form-control" value="<?= esc(old('published_at')) ?>">
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="is_featured" id="is_featured" class="form-check-input" value="1">
                    <label class="form-check-label" for="is_featured">Featured Post</label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="is_premium" id="is_premium" class="form-check-input" value="1">
                    <label class="form-check-label" for="is_premium"><i class="fas fa-lock fa-xs text-warning mr-1"></i>Members Only</label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="share_on_publish" id="share_on_publish" class="form-check-input" value="1" checked>
                    <label class="form-check-label" for="share_on_publish">Share to social on publish</label>
                </div>
                <button type="submit" class="btn btn-primary btn-block mt-3">Save Post</button>
            </div>
        </div>

        <!-- Featured Image -->
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Featured Image</h6></div>
            <div class="card-body">
                <input type="text" name="featured_image" class="form-control" placeholder="URL or upload path…" value="<?= esc(old('featured_image')) ?>">
            </div>
        </div>

        <!-- Categories -->
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Categories</h6></div>
            <div class="card-body" style="max-height:200px;overflow-y:auto">
                <?php foreach ($categories as $cat): ?>
                <div class="form-check">
                    <input type="checkbox" name="categories[]" id="cat<?= $cat->id ?>" value="<?= $cat->id ?>" class="form-check-input">
                    <label class="form-check-label" for="cat<?= $cat->id ?>"><?= esc($cat->name) ?></label>
                </div>
                <?php endforeach; ?>
                <?php if (empty($categories)): ?>
                    <p class="text-muted small mb-0">No categories yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tags -->
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Tags</h6></div>
            <div class="card-body">
                <input type="text" name="tags_raw" class="form-control" placeholder="tag1, tag2, tag3" value="<?= esc(old('tags_raw')) ?>">
                <small class="text-muted">Comma-separated</small>
            </div>
        </div>

    </div>
</div>
</form>

<script>
(function() {
    var sel = document.querySelector('select[name="status"]');
    var grp = document.getElementById('published-at-group');
    function toggle() { grp.style.display = sel.value === 'scheduled' ? 'block' : 'none'; }
    sel.addEventListener('change', toggle);
    toggle();
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php $extra_scripts = <<<'HTML'
<link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-bs4.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-bs4.min.js"></script>
<script src="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css">
<script>
var summernoteInitialized = false;
var simplemdeEditor = null;

function switchEditor(type) {
    document.getElementById('content_type').value = type;
    if (type === 'html') {
        document.getElementById('editor-html').style.display = 'block';
        document.getElementById('editor-md').style.display = 'none';
        if (!summernoteInitialized) {
            $('#content-html').summernote({ height: 400, toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['font', ['strikethrough']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture', 'hr']],
                ['view', ['codeview', 'fullscreen']]
            ]});
            summernoteInitialized = true;
        }
    } else {
        if (summernoteInitialized) {
            document.getElementById('content-html').value = $('#content-html').summernote('code');
        }
        document.getElementById('editor-html').style.display = 'none';
        document.getElementById('editor-md').style.display = 'block';
        if (!simplemdeEditor) {
            simplemdeEditor = new SimpleMDE({ element: document.getElementById('content-md') });
        }
    }
}
document.addEventListener('DOMContentLoaded', function() {
    switchEditor('html');
    document.querySelectorAll('input[name="editor_type"]').forEach(function(r) {
        r.addEventListener('change', function() { switchEditor(this.value); });
    });
});
</script>
HTML;
?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
