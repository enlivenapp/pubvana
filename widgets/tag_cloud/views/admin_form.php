<div class="mb-3">
    <label class="form-label">Title</label>
    <input type="text" name="options[title]" class="form-control" value="<?= esc($options['title'] ?? 'Tags') ?>">
</div>
<div class="mb-3">
    <label class="form-label">Max Tags</label>
    <input type="number" name="options[max_tags]" class="form-control" value="<?= esc($options['max_tags'] ?? 30) ?>" min="1" max="100">
</div>
