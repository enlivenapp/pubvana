<?php
/**
 * Tag listing - admin page.
 *
 * @var string                                 $pageTitle
 * @var string                                 $adminBase
 * @var \Pubvana\Plugins\Blog\Models\Tag[]     $tags
 */
?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tags)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-secondary py-4">No tags found. Tags are created when assigned to posts.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tags as $tag): ?>
                        <tr>
                            <td><?= htmlspecialchars($tag->name) ?></td>
                            <td><code><?= htmlspecialchars($tag->slug) ?></code></td>
                            <td>
                                <form method="POST" action="<?= $adminBase ?>/tags/<?= (int) $tag->id ?>/delete"
                                      class="d-inline" onsubmit="return confirm('Delete this tag?')">
                                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
