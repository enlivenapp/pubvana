<div class="mb-3">
    <label class="form-label">Title</label>
    <input type="text" name="options[title]" class="form-control" value="<?= esc($options['title'] ?? 'Recent Posts') ?>">
</div>
<div class="mb-3">
    <label class="form-label">Number of Posts</label>
    <input type="number" name="options[count]" class="form-control" value="<?= esc($options['count'] ?? 5) ?>" min="1" max="20">
</div>
<div class="mb-3 form-check">
    <input type="checkbox" name="options[show_date]" id="show_date" class="form-check-input" value="1" <?= !empty($options['show_date']) ? 'checked' : '' ?>>
    <label class="form-check-label" for="show_date">Show Date</label>
</div>
<div class="mb-3 form-check">
    <input type="checkbox" name="options[show_excerpt]" id="show_excerpt" class="form-check-input" value="1" <?= !empty($options['show_excerpt']) ? 'checked' : '' ?>>
    <label class="form-check-label" for="show_excerpt">Show Excerpt</label>
</div>
