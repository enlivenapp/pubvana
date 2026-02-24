<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">New Page</h1>
    <a href="<?= base_url('admin/pages') ?>" class="btn btn-sm btn-outline-secondary">Back</a>
</div>

<form method="POST" action="<?= base_url('admin/pages/create') ?>">
<?= csrf_field() ?>
<input type="hidden" name="content_type" id="content_type" value="html">
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="form-group">
                    <label class="font-weight-bold">Title *</label>
                    <input type="text" name="title" class="form-control form-control-lg" required value="<?= esc(old('title')) ?>">
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Slug *</label>
                    <input type="text" name="slug" class="form-control" value="<?= esc(old('slug')) ?>" placeholder="auto-generated from title if left blank">
                </div>
                <div class="form-group">
                    <label class="font-weight-bold d-block">Editor</label>
                    <div class="btn-group btn-group-sm">
                        <input type="radio" class="btn-check" name="editor_type" id="et_html" value="html" checked>
                        <label class="btn btn-outline-primary" for="et_html">HTML</label>
                        <input type="radio" class="btn-check" name="editor_type" id="et_md" value="markdown">
                        <label class="btn btn-outline-secondary" for="et_md">Markdown</label>
                    </div>
                </div>
                <div id="editor-html">
                    <textarea name="content" id="content-html" class="form-control" rows="15"><?= esc(old('content')) ?></textarea>
                </div>
                <div id="editor-md" style="display:none">
                    <textarea name="content_md" id="content-md" class="form-control" rows="15"></textarea>
                </div>
            </div>
        </div>
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">SEO</h6></div>
            <div class="card-body">
                <div class="form-group"><label>Meta Title</label><input type="text" name="meta_title" class="form-control" value="<?= esc(old('meta_title')) ?>"></div>
                <div class="form-group mb-0"><label>Meta Description</label><textarea name="meta_description" class="form-control" rows="2"><?= esc(old('meta_description')) ?></textarea></div>
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
                        <option value="draft" <?= old('status','draft')==='draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= old('status','draft')==='published' ? 'selected' : '' ?>>Published</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Save Page</button>
            </div>
        </div>
    </div>
</div>
</form>

<?php $content = ob_get_clean(); ?>
<?php $extra_scripts = <<<'HTML'
<link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-bs4.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-bs4.min.js"></script>
<script src="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css">
<script>
var mdeInit = false; var sme = null;
function switchEditor(t) {
    document.getElementById('content_type').value = t;
    document.getElementById('editor-html').style.display = t==='html'?'block':'none';
    document.getElementById('editor-md').style.display  = t==='markdown'?'block':'none';
    if(t==='html' && !mdeInit){ $('#content-html').summernote({height:400,toolbar:[['style',['bold','italic','underline','clear']],['para',['ul','ol','paragraph']],['insert',['link','picture','hr']],['view',['codeview','fullscreen']]]}); mdeInit=true; }
    if(t==='markdown' && !sme){ sme=new SimpleMDE({element:document.getElementById('content-md')}); }
}
document.addEventListener('DOMContentLoaded',function(){
    switchEditor('html');
    document.querySelectorAll('input[name="editor_type"]').forEach(r=>r.addEventListener('change',function(){switchEditor(this.value);}));
    var slugEdited = false;
    var titleEl = document.querySelector('[name="title"]');
    var slugEl  = document.querySelector('[name="slug"]');
    if(slugEl.value !== '') slugEdited = true;
    slugEl.addEventListener('input', function(){ if(this.value !== '') slugEdited = true; else slugEdited = false; });
    titleEl.addEventListener('input', function(){
        if(slugEdited) return;
        var s = this.value.toLowerCase().trim()
            .replace(/&/g,'and')
            .replace(/[^a-z0-9\s-]/g,'')
            .replace(/[\s]+/g,'-')
            .replace(/-+/g,'-')
            .replace(/^-|-$/g,'');
        slugEl.value = s;
    });
});
</script>
HTML;
?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
