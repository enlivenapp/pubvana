<ul class="list-group">
	<?php if ($archives): ?>
		<?php foreach ($archives as $archive_item): ?>
			<li class="list-group-item text-center"><a href="<?= archive_url($archive_item->url); ?>"><?= $archive_item->date_posted ?> (<?= $archive_item->posts_count; ?>)</a></li>
		<?php endforeach; ?>
	<?php endif; ?>
</ul>
