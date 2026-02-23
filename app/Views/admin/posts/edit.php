<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Edit Post: <?= esc($post->title) ?></h1>
    <div>
        <a href="<?= post_url($post->slug) ?>" class="btn btn-sm btn-outline-secondary" target="_blank">View Post</a>
        <a href="<?= base_url('admin/posts') ?>" class="btn btn-sm btn-outline-secondary ml-1">Back</a>
    </div>
</div>

<form method="POST" action="<?= base_url('admin/posts/' . $post->id . '/edit') ?>">
<?= csrf_field() ?>
<input type="hidden" name="content_type" id="content_type" value="<?= esc($post->content_type ?? 'html') ?>">

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="form-group">
                    <label class="font-weight-bold">Title *</label>
                    <input type="text" name="title" class="form-control form-control-lg" value="<?= esc(old('title', $post->title)) ?>" required>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold d-block">Editor</label>
                    <div class="btn-group btn-group-sm">
                        <input type="radio" class="btn-check" name="editor_type" id="et_html" value="html" <?= ($post->content_type ?? 'html') === 'html' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary" for="et_html"><i class="fas fa-code"></i> HTML Editor</label>
                        <input type="radio" class="btn-check" name="editor_type" id="et_md" value="markdown" <?= ($post->content_type ?? '') === 'markdown' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-secondary" for="et_md"><i class="fab fa-markdown"></i> Markdown</label>
                    </div>
                </div>

                <div id="editor-html" style="<?= ($post->content_type ?? 'html') === 'markdown' ? 'display:none' : '' ?>">
                    <textarea name="content" id="content-html" class="form-control" rows="15"><?= esc($post->content) ?></textarea>
                </div>
                <div id="editor-md" style="<?= ($post->content_type ?? 'html') !== 'markdown' ? 'display:none' : '' ?>">
                    <textarea name="content_md" id="content-md" class="form-control" rows="15"><?= esc($post->content) ?></textarea>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Excerpt</h6></div>
            <div class="card-body">
                <textarea name="excerpt" class="form-control" rows="3"><?= esc($post->excerpt) ?></textarea>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">SEO</h6></div>
            <div class="card-body">
                <div class="form-group">
                    <label>Meta Title</label>
                    <input type="text" name="meta_title" class="form-control" value="<?= esc($post->meta_title) ?>">
                </div>
                <div class="form-group mb-0">
                    <label>Meta Description</label>
                    <textarea name="meta_description" class="form-control" rows="2"><?= esc($post->meta_description) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Publish</h6></div>
            <div class="card-body">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="draft"     <?= $post->status === 'draft'     ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= $post->status === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="scheduled" <?= $post->status === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Publish Date</label>
                    <input type="datetime-local" name="published_at" class="form-control" value="<?= $post->published_at ? date('Y-m-d\TH:i', strtotime($post->published_at)) : '' ?>">
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="is_featured" id="is_featured" class="form-check-input" value="1" <?= $post->is_featured ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_featured">Featured Post</label>
                </div>
                <button type="submit" class="btn btn-primary btn-block mt-3">Update Post</button>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Featured Image</h6></div>
            <div class="card-body">
                <?php if ($post->featured_image): ?>
                    <img src="<?= esc(base_url($post->featured_image)) ?>" class="img-fluid rounded mb-2" alt="">
                <?php endif; ?>
                <input type="text" name="featured_image" class="form-control" value="<?= esc($post->featured_image) ?>">
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Categories</h6></div>
            <div class="card-body" style="max-height:200px;overflow-y:auto">
                <?php foreach ($categories as $cat): ?>
                <div class="form-check">
                    <input type="checkbox" name="categories[]" id="cat<?= $cat->id ?>" value="<?= $cat->id ?>" class="form-check-input"
                        <?= in_array($cat->id, $selected_cats ?? []) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="cat<?= $cat->id ?>"><?= esc($cat->name) ?></label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Tags</h6></div>
            <div class="card-body">
                <input type="text" name="tags_raw" class="form-control" value="<?= esc($tags_raw ?? '') ?>" placeholder="tag1, tag2">
            </div>
        </div>
    </div>
</div>
</form>

<?php $content = ob_get_clean(); ?>
<?php $extra_scripts = <<<HTML
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
            \$('#content-html').summernote({ height: 400, toolbar: [
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
            document.getElementById('content-html').value = \$('#content-html').summernote('code');
        }
        document.getElementById('editor-html').style.display = 'none';
        document.getElementById('editor-md').style.display = 'block';
        if (!simplemdeEditor) {
            simplemdeEditor = new SimpleMDE({ element: document.getElementById('content-md') });
        }
    }
}
document.addEventListener('DOMContentLoaded', function() {
    var type = document.getElementById('content_type').value;
    if (type === 'html') switchEditor('html');
    else switchEditor('markdown');
    document.querySelectorAll('input[name="editor_type"]').forEach(function(r) {
        r.addEventListener('change', function() { switchEditor(this.value); });
    });
});
</script>
HTML;
?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
