<?php
/**
 * Comment detail with moderation actions - admin page.
 *
 * @var \Pubvana\Plugins\Comments\Models\Comment $comment
 * @var array|null                                 $hostItem
 * @var string                                     $adminBase
 */
?>

<div class="mb-3">
    <a href="<?= $adminBase ?>" class="btn btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i>Back to Comments
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Comment Body</h3>
            </div>
            <div class="card-body">
                <div class="comment-body">
                    <?= $comment->body ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Details</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">Status</dt>
                    <dd class="col-7">
                        <?php
                        $statusBadge = match ($comment->status) {
                            'approved' => 'bg-success-lt',
                            'pending'  => 'bg-warning-lt',
                            'rejected' => 'bg-danger-lt',
                            default    => 'bg-secondary-lt',
                        };
                        ?>
                        <span class="badge <?= $statusBadge ?>"><?= htmlspecialchars((string) $comment->status) ?></span>
                    </dd>

                    <dt class="col-5">Author</dt>
                    <dd class="col-7">
                        <?php if ($comment->user_id): ?>
                            <i class="ti ti-user me-1"></i>User #<?= (int) $comment->user_id ?>
                        <?php else: ?>
                            <i class="ti ti-user-question me-1"></i><?= htmlspecialchars((string) $comment->guest_name) ?: 'Anonymous' ?>
                        <?php endif; ?>
                    </dd>

                    <?php if ($comment->guest_email): ?>
                        <dt class="col-5">Email</dt>
                        <dd class="col-7"><?= htmlspecialchars((string) $comment->guest_email) ?></dd>
                    <?php endif; ?>

                    <?php if ($comment->guest_website): ?>
                        <dt class="col-5">Website</dt>
                        <dd class="col-7">
                            <a href="<?= htmlspecialchars((string) $comment->guest_website) ?>" target="_blank" rel="noopener noreferrer">
                                <?= htmlspecialchars((string) $comment->guest_website) ?>
                            </a>
                        </dd>
                    <?php endif; ?>

                    <dt class="col-5">Content</dt>
                    <dd class="col-7">
                        <?php if ($hostItem): ?>
                            <a href="<?= htmlspecialchars($hostItem['url']) ?>" target="_blank" rel="noopener noreferrer">
                                <?= htmlspecialchars($hostItem['title']) ?>
                            </a>
                        <?php else: ?>
                            <?= htmlspecialchars((string) $comment->commentable_type) ?> #<?= (int) $comment->commentable_id ?>
                        <?php endif; ?>
                    </dd>

                    <?php if ($comment->parent_id): ?>
                        <dt class="col-5">Reply to</dt>
                        <dd class="col-7">
                            <a href="<?= $adminBase ?>/<?= (int) $comment->parent_id ?>">Comment #<?= (int) $comment->parent_id ?></a>
                        </dd>
                    <?php endif; ?>

                    <?php if ($comment->ip_address): ?>
                        <dt class="col-5">IP Address</dt>
                        <dd class="col-7"><?= htmlspecialchars((string) $comment->ip_address) ?></dd>
                    <?php endif; ?>

                    <dt class="col-5">Created</dt>
                    <dd class="col-7">
                        <?= $comment->created_at ? date('M j, Y g:ia', strtotime((string) $comment->created_at)) : '-' ?>
                    </dd>

                    <dt class="col-5">Updated</dt>
                    <dd class="col-7">
                        <?= $comment->updated_at ? date('M j, Y g:ia', strtotime((string) $comment->updated_at)) : '-' ?>
                    </dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Actions</h3>
            </div>
            <div class="card-body">
                <div class="d-flex gap-2">
                    <?php if ($comment->status !== 'approved'): ?>
                        <form method="post" action="<?= $adminBase ?>/<?= (int) $comment->id ?>/approve">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-outline-success">
                                <i class="ti ti-check me-1"></i>Approve
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($comment->status !== 'rejected'): ?>
                        <form method="post" action="<?= $adminBase ?>/<?= (int) $comment->id ?>/reject">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-outline-warning">
                                <i class="ti ti-x me-1"></i>Reject
                            </button>
                        </form>
                    <?php endif; ?>

                    <form method="post" action="<?= $adminBase ?>/<?= (int) $comment->id ?>/delete" onsubmit="return confirm('Delete this comment permanently?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="ti ti-trash me-1"></i>Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
