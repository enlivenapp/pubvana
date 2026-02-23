<div class="mb-3">
    <label class="form-label">Title</label>
    <input type="text" name="options[title]" class="form-control" value="<?= esc($options['title'] ?? 'Archives') ?>">
</div>
<div class="mb-3">
    <label class="form-label">Format</label>
    <select name="options[format]" class="form-select">
        <option value="monthly" <?= ($options['format'] ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
        <option value="yearly"  <?= ($options['format'] ?? '') === 'yearly'  ? 'selected' : '' ?>>Yearly</option>
    </select>
</div>
