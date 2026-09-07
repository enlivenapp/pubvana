<?php
/**
 * Category listing - admin page.
 *
 * @var string                                       $pageTitle
 * @var string                                       $adminBase
 * @var \Pubvana\Plugins\Blog\Models\Category[]      $categories
 */
?>

<div class="d-flex align-items-center justify-content-end mb-4">
    <a href="<?= $adminBase ?>/categories/create" class="btn btn-primary">
        <i class="ti ti-plus me-1"></i> New Category
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Parent</th>
                    <th>Slug</th>
                    <th>Description</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">No categories found.</td>
                    </tr>
                <?php else: ?>
                    <?php
                    $catMap = [];
                    foreach ($categories as $_cat) {
                        $catMap[(int) $_cat->id] = $_cat->name;
                    }
                    ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td>
                                <a href="<?= $adminBase ?>/categories/<?= (int) $cat->id ?>/edit">
                                    <?= htmlspecialchars($cat->name) ?>
                                </a>
                            </td>
                            <td>
                                <?php if (!empty($cat->parent_id) && isset($catMap[(int) $cat->parent_id])): ?>
                                    <?= htmlspecialchars($catMap[(int) $cat->parent_id]) ?>
                                <?php else: ?>
                                    <span class="text-secondary">-</span>
                                <?php endif; ?>
                            </td>
                            <td><code><?= htmlspecialchars($cat->slug) ?></code></td>
                            <td><?= htmlspecialchars($cat->description ?? '') ?></td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <a href="<?= $adminBase ?>/categories/<?= (int) $cat->id ?>/edit" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="POST" action="<?= $adminBase ?>/categories/<?= (int) $cat->id ?>/delete"
                                          class="d-inline" onsubmit="return confirm('Delete this category?')">
                                        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
