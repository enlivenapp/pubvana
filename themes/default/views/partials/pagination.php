<?php if ($pager): ?>
<nav aria-label="Page navigation" class="mt-4">
    <ul class="pagination justify-content-center">
        <?php
        $links = $pager->links('default', 'default_full');
        ?>
        <?= $links ?>
    </ul>
</nav>
<?php endif; ?>
