<div class="mb-3">
    <label class="form-label">Title</label>
    <input type="text" name="options[title]" class="form-control" value="<?= esc($options['title'] ?? 'Recent Comments') ?>">
</div>
<div class="mb-3">
    <label class="form-label">Number of Comments</label>
    <input type="number" name="options[count]" class="form-control" value="<?= esc($options['count'] ?? 5) ?>" min="1" max="20">
</div>
