<?php

use CodeIgniter\Pager\PagerRenderer;

/**
 * Bootstrap 4 pagination template for admin panel.
 *
 * @var PagerRenderer $pager
 */
$pager->setSurroundCount(2);
?>

<nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
        <?php if ($pager->hasPrevious()): ?>
        <li class="page-item">
            <a class="page-link" href="<?= $pager->getFirst() ?>" aria-label="First">«</a>
        </li>
        <li class="page-item">
            <a class="page-link" href="<?= $pager->getPrevious() ?>" aria-label="Previous">‹</a>
        </li>
        <?php else: ?>
        <li class="page-item disabled"><span class="page-link">«</span></li>
        <li class="page-item disabled"><span class="page-link">‹</span></li>
        <?php endif; ?>

        <?php foreach ($pager->links() as $link): ?>
        <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
            <a class="page-link" href="<?= $link['uri'] ?>"><?= $link['title'] ?></a>
        </li>
        <?php endforeach; ?>

        <?php if ($pager->hasNext()): ?>
        <li class="page-item">
            <a class="page-link" href="<?= $pager->getNext() ?>" aria-label="Next">›</a>
        </li>
        <li class="page-item">
            <a class="page-link" href="<?= $pager->getLast() ?>" aria-label="Last">»</a>
        </li>
        <?php else: ?>
        <li class="page-item disabled"><span class="page-link">›</span></li>
        <li class="page-item disabled"><span class="page-link">»</span></li>
        <?php endif; ?>
    </ul>
</nav>
