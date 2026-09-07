<?php
/**
 * Comment moderation list - admin page.
 *
 * @var \Pubvana\Plugins\Comments\Models\Comment[] $comments
 * @var array<string, int>                           $counts
 * @var string|null                                  $status
 * @var int                                          $page
 * @var int                                          $totalPages
 * @var string                                       $adminBase
 */

$currentFilter = $status ?? 'all';
?>

<div class="d-flex align-items-center mb-4">
    <div class="btn-list">
        <a href="<?= $adminBase ?>" class="btn btn-sm <?= $currentFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>">All (<?= $counts['all'] ?>)</a>
        <a href="<?= $adminBase ?>?status=pending" class="btn btn-sm <?= $currentFilter === 'pending' ? 'btn-primary' : 'btn-outline-secondary' ?>">Pending (<?= $counts['pending'] ?>)</a>
        <a href="<?= $adminBase ?>?status=approved" class="btn btn-sm <?= $currentFilter === 'approved' ? 'btn-primary' : 'btn-outline-secondary' ?>">Approved (<?= $counts['approved'] ?>)</a>
        <a href="<?= $adminBase ?>?status=rejected" class="btn btn-sm <?= $currentFilter === 'rejected' ? 'btn-primary' : 'btn-outline-secondary' ?>">Rejected (<?= $counts['rejected'] ?>)</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Author</th>
                    <th>Comment</th>
                    <th>On</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($comments)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">No comments found.</td>
                    </tr>
                <?php else: ?>
                    <?php /** @var \Pubvana\Plugins\Comments\Models\Comment $comment */ ?>
                    <?php foreach ($comments as $comment): ?>
                        <tr>
                            <td>
                                <?php if ($comment->user_id): ?>
                                    <span class="text-secondary" title="Registered user #<?= (int) $comment->user_id ?>">
                                        <i class="ti ti-user me-1"></i>User #<?= (int) $comment->user_id ?>
                                    </span>
                                <?php else: ?>
                                    <span title="<?= htmlspecialchars((string) $comment->guest_email) ?>">
                                        <i class="ti ti-user-question me-1"></i><?= htmlspecialchars((string) $comment->guest_name) ?: 'Anonymous' ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= $adminBase ?>/<?= (int) $comment->id ?>">
                                    <?= htmlspecialchars(mb_strimwidth(trim(strip_tags((string) $comment->body)), 0, 80, '...')) ?>
                                </a>
                            </td>
                            <td>
                                <span class="text-secondary">
                                    <?= htmlspecialchars((string) $comment->commentable_type) ?> #<?= (int) $comment->commentable_id ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $statusBadge = match ($comment->status) {
                                    'approved' => 'bg-success-lt',
                                    'pending'  => 'bg-warning-lt',
                                    'rejected' => 'bg-danger-lt',
                                    default    => 'bg-secondary-lt',
                                };
                                ?>
                                <span class="badge <?= $statusBadge ?>"><?= htmlspecialchars((string) $comment->status) ?></span>
                            </td>
                            <td class="text-secondary">
                                <?= $comment->created_at ? date('M j, Y g:ia', strtotime((string) $comment->created_at)) : '-' ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <?php if ($comment->status !== 'approved'): ?>
                                        <form method="post" action="<?= $adminBase ?>/<?= (int) $comment->id ?>/approve" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Approve">
                                                <i class="ti ti-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($comment->status !== 'rejected'): ?>
                                        <form method="post" action="<?= $adminBase ?>/<?= (int) $comment->id ?>/reject" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Reject">
                                                <i class="ti ti-x"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" action="<?= $adminBase ?>/<?= (int) $comment->id ?>/delete" class="d-inline" onsubmit="return confirm('Delete this comment permanently?')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="ti ti-trash"></i>
                                        </button>
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

<?php if ($totalPages > 1): ?>
    <div class="mt-3 d-flex justify-content-center">
        <ul class="pagination">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= $adminBase ?>?page=<?= $page - 1 ?><?= $status ? '&status=' . $status : '' ?>">
                        <i class="ti ti-chevron-left"></i>
                    </a>
                </li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= $adminBase ?>?page=<?= $i ?><?= $status ? '&status=' . $status : '' ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= $adminBase ?>?page=<?= $page + 1 ?><?= $status ? '&status=' . $status : '' ?>">
                        <i class="ti ti-chevron-right"></i>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
<?php endif; ?>
