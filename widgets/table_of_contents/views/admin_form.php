<div class="mb-3">
    <label class="form-label">Title</label>
    <input type="text" name="options[title]" class="form-control" value="<?= esc($options['title'] ?? 'Contents') ?>">
</div>
<div class="mb-3">
    <label class="form-label">Min Headings (hide if fewer)</label>
    <input type="number" name="options[min_headings]" class="form-control" value="<?= esc($options['min_headings'] ?? 2) ?>" min="1" max="20">
</div>
<div class="mb-3">
    <label class="form-label">Max Depth</label>
    <select name="options[max_depth]" class="form-control">
        <option value="h2" <?= ($options['max_depth'] ?? 'h3') === 'h2' ? 'selected' : '' ?>>H2 only</option>
        <option value="h3" <?= ($options['max_depth'] ?? 'h3') === 'h3' ? 'selected' : '' ?>>H2 + H3</option>
        <option value="h4" <?= ($options['max_depth'] ?? 'h3') === 'h4' ? 'selected' : '' ?>>H2 + H3 + H4</option>
    </select>
</div>
