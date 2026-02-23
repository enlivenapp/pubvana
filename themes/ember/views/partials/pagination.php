<?php if (isset($pager)): ?>
<nav class="mt-4" aria-label="Page navigation">
    <?= $pager->links('default', 'default_full') ?>
</nav>
<?php endif; ?>
