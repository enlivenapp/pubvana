<?php
/**
 * Post revision history - admin page.
 *
 * @var string                                       $pageTitle
 * @var string                                       $adminBase
 * @var \Pubvana\Plugins\Blog\Models\Post            $post
 * @var \Pubvana\Plugins\Blog\Models\PostRevision[]  $revisions
 */
?>

<div class="d-flex align-items-center justify-content-end mb-4">
    <a href="<?= $adminBase ?>/<?= (int) $post->id ?>/edit" class="btn btn-outline-secondary">Back to Edit</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($revisions)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">No revisions found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($revisions as $i => $r): ?>
                        <tr>
                            <td><?= (int) $r->id ?></td>
                            <td><?= htmlspecialchars($r->title) ?></td>
                            <td>
                                <span class="badge bg-<?= $r->status === 'published' ? 'success' : 'secondary' ?>">
                                    <?= htmlspecialchars($r->status) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($r->created_at) ?></td>
                            <td>
                                <form method="POST"
                                      action="<?= $adminBase ?>/<?= (int) $post->id ?>/restore/<?= (int) $r->id ?>"
                                      onsubmit="return confirm('Restore this revision? Current content will be saved as a new revision first.')">
                                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                    <button class="btn btn-sm btn-outline-primary">Restore</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
