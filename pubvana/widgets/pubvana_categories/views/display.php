<ul class="list-group">
	<?php if ($categories): ?>
		<?php foreach ($categories as $category): ?>
			<li class="list-group-item text-center"><a href="<?= category_url($category->url_name); ?>"> <?php echo $category->name; ?> (<?php echo $category->posts_count; ?>)</a></li>
		<?php endforeach; ?>
	<?php endif; ?>
</ul>
