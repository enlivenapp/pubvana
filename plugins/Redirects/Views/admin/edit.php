<?php
/**
 * Edit redirect form.
 *
 * @var string $pageTitle
 * @var \Pubvana\Plugins\Redirects\Models\Redirect $redirect
 * @var array $targetSuggestions
 * @var string $adminBase
 */
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <a href="<?= $adminBase ?>" class="btn btn-outline-secondary">Back</a>
    <div class="text-secondary small">
        Hits: <?= (int) $redirect->hit_count ?>
        <?php if (!empty($redirect->last_hit_at)): ?>
            <span class="ms-2">Last hit: <?= htmlspecialchars((string) $redirect->last_hit_at) ?></span>
        <?php endif; ?>
    </div>
</div>

<form method="POST" action="<?= $adminBase ?>/<?= (int) $redirect->id ?>/update">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="source_path">Source Path</label>
                        <input type="text" name="source_path" id="source_path" class="form-control" required
                               value="<?= htmlspecialchars($redirect->source_path) ?>">
                        <div class="form-hint">Exact-path match only. Query strings are ignored when matching.</div>
                    </div>

                    <?php if (!empty($targetSuggestions)): ?>
                    <div class="mb-3">
                        <label class="form-label">Quick Target</label>
                        <div class="position-relative" id="quickTargetWrapper">
                            <input type="text" class="form-control" id="quickTargetSearch"
                                   placeholder="Search pages, blog posts..." autocomplete="off">
                            <div class="list-group position-absolute w-100 shadow-sm d-none card"
                                 id="quickTargetList" style="z-index: 1000; max-height: 250px; overflow-y: auto;">
                                <?php foreach ($targetSuggestions as $groupName => $routes): ?>
                                    <div class="list-group-item list-group-item-secondary py-1 px-2 small fw-bold quick-target-group">
                                        <?= htmlspecialchars($groupName) ?>
                                    </div>
                                    <?php foreach ($routes as $route): ?>
                                    <a href="#" class="list-group-item list-group-item-action py-1 px-3 quick-target-item"
                                       data-url="<?= htmlspecialchars($route['url']) ?>"
                                       data-search="<?= htmlspecialchars(strtolower($route['label'] . ' ' . $route['url'])) ?>">
                                        <?= htmlspecialchars($route['label']) ?>
                                        <small class="text-secondary"><?= htmlspecialchars($route['url']) ?></small>
                                    </a>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label" for="target_url">Target URL or Path</label>
                        <input type="text" name="target_url" id="target_url" class="form-control" required
                               value="<?= htmlspecialchars($redirect->target_url) ?>">
                    </div>

                    <div class="mb-0">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="4"><?= htmlspecialchars((string) ($redirect->notes ?? '')) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Settings</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="status_code">Status Code</label>
                        <select name="status_code" id="status_code" class="form-select">
                            <option value="301" <?= (int) $redirect->status_code === 301 ? 'selected' : '' ?>>301 Permanent</option>
                            <option value="302" <?= (int) $redirect->status_code === 302 ? 'selected' : '' ?>>302 Temporary</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" name="enabled" value="1" class="form-check-input"
                                   <?= (int) $redirect->enabled === 1 ? 'checked' : '' ?>>
                            <span class="form-check-label">Enabled</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Save Redirect</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
(function() {
    var search = document.getElementById('quickTargetSearch');
    var list = document.getElementById('quickTargetList');
    if (!search || !list) return;

    var items = list.querySelectorAll('.quick-target-item');
    var groups = list.querySelectorAll('.quick-target-group');
    var targetInput = document.getElementById('target_url');

    search.addEventListener('focus', function() {
        list.classList.remove('d-none');
    });

    search.addEventListener('input', function() {
        var q = this.value.toLowerCase();
        list.classList.remove('d-none');

        items.forEach(function(item) {
            item.style.display = (!q || item.dataset.search.indexOf(q) !== -1) ? '' : 'none';
        });

        groups.forEach(function(grp) {
            var next = grp.nextElementSibling;
            var hasVisible = false;
            while (next && !next.classList.contains('quick-target-group')) {
                if (next.classList.contains('quick-target-item') && next.style.display !== 'none') {
                    hasVisible = true;
                }
                next = next.nextElementSibling;
            }
            grp.style.display = hasVisible ? '' : 'none';
        });
    });

    items.forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            targetInput.value = this.dataset.url;
            search.value = '';
            list.classList.add('d-none');
            targetInput.focus();
        });
    });

    document.addEventListener('click', function(e) {
        var wrapper = document.getElementById('quickTargetWrapper');
        if (wrapper && !wrapper.contains(e.target)) {
            list.classList.add('d-none');
        }
    });
}());
</script>
