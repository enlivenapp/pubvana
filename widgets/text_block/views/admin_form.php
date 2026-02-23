<div class="mb-3">
    <label class="form-label">Title</label>
    <input type="text" name="options[title]" class="form-control" value="<?= esc($options['title'] ?? '') ?>">
</div>
<div class="mb-3">
    <label class="form-label">Content (HTML allowed)</label>
    <textarea name="options[content]" class="form-control" rows="6"><?= esc($options['content'] ?? '') ?></textarea>
</div>
