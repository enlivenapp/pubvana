<div class="mb-3">
    <label class="form-label">Title</label>
    <input type="text" name="options[title]" class="form-control" value="<?= esc($options['title'] ?? 'Related Posts') ?>">
</div>
<div class="mb-3">
    <label class="form-label">Number of Posts</label>
    <input type="number" name="options[count]" class="form-control" value="<?= esc($options['count'] ?? 4) ?>" min="1" max="12">
</div>
<div class="mb-3 form-check">
    <input type="checkbox" name="options[show_thumbnail]" id="show_thumbnail" class="form-check-input" value="1" <?= !empty($options['show_thumbnail']) ? 'checked' : '' ?>>
    <label class="form-check-label" for="show_thumbnail">Show Thumbnail</label>
</div>
