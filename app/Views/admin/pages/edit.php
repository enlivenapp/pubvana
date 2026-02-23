<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Edit: <?= esc($page->title) ?></h1>
    <a href="<?= base_url('admin/pages') ?>" class="btn btn-sm btn-outline-secondary">Back</a>
</div>

<form method="POST" action="<?= base_url('admin/pages/' . $page->id . '/edit') ?>">
<?= csrf_field() ?>
<input type="hidden" name="content_type" id="content_type" value="<?= esc($page->content_type ?? 'html') ?>">
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="form-group">
                    <label class="font-weight-bold">Title *</label>
                    <input type="text" name="title" class="form-control form-control-lg" required value="<?= esc($page->title) ?>">
                </div>
                <div class="form-group">
                    <label>Slug <small class="text-muted">(cannot change)</small></label>
                    <input type="text" class="form-control" value="<?= esc($page->slug) ?>" readonly>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold d-block">Editor</label>
                    <div class="btn-group btn-group-sm">
                        <input type="radio" class="btn-check" name="editor_type" id="et_html" value="html" <?= ($page->content_type ?? 'html') === 'html' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary" for="et_html">HTML</label>
                        <input type="radio" class="btn-check" name="editor_type" id="et_md" value="markdown" <?= ($page->content_type ?? '') === 'markdown' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-secondary" for="et_md">Markdown</label>
                    </div>
                </div>
                <div id="editor-html" style="<?= ($page->content_type ?? 'html') === 'markdown' ? 'display:none' : '' ?>">
                    <textarea name="content" id="content-html" class="form-control" rows="15"><?= esc($page->content) ?></textarea>
                </div>
                <div id="editor-md" style="<?= ($page->content_type ?? 'html') !== 'markdown' ? 'display:none' : '' ?>">
                    <textarea name="content_md" id="content-md" class="form-control" rows="15"><?= esc($page->content) ?></textarea>
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
                        <option value="draft" <?= $page->status === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= $page->status === 'published' ? 'selected' : '' ?>>Published</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Update Page</button>
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
var mdeInit=false,sme=null;
function switchEditor(t){document.getElementById('content_type').value=t;document.getElementById('editor-html').style.display=t==='html'?'block':'none';document.getElementById('editor-md').style.display=t==='markdown'?'block':'none';if(t==='html'&&!mdeInit){$('#content-html').summernote({height:400,toolbar:[['style',['bold','italic','underline','clear']],['para',['ul','ol','paragraph']],['insert',['link','picture','hr']],['view',['codeview','fullscreen']]]});mdeInit=true;}if(t==='markdown'&&!sme){sme=new SimpleMDE({element:document.getElementById('content-md')});}}
document.addEventListener('DOMContentLoaded',function(){var t=document.getElementById('content_type').value;switchEditor(t);document.querySelectorAll('input[name="editor_type"]').forEach(r=>r.addEventListener('change',function(){switchEditor(this.value);}));});
</script>
HTML;
?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
