<div class="mb-3">
    <label class="form-label">Title</label>
    <input type="text" name="options[title]" class="form-control" value="<?= esc($options['title'] ?? 'Categories') ?>">
</div>
<div class="mb-3 form-check">
    <input type="checkbox" name="options[show_count]" id="show_count" class="form-check-input" value="1" <?= !empty($options['show_count']) ? 'checked' : '' ?>>
    <label class="form-check-label" for="show_count">Show Post Count</label>
</div>
