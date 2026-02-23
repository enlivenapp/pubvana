<div class="mb-3">
    <label class="form-label">Title</label>
    <input type="text" name="options[title]" class="form-control" value="<?= esc($options['title'] ?? 'Follow Us') ?>">
</div>
<div class="mb-3">
    <label class="form-label">Style</label>
    <select name="options[style]" class="form-select">
        <option value="icons" <?= ($options['style'] ?? '') === 'icons' ? 'selected' : '' ?>>Icons only</option>
        <option value="icons+text" <?= ($options['style'] ?? '') === 'icons+text' ? 'selected' : '' ?>>Icons + Text</option>
    </select>
</div>
