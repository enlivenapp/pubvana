
<?php if ($links): ?>
<ul class="list-group">
		<?php foreach ($links as $link): ?>
			<li class="list-group-item text-center"><a href="<?php echo $link->url; ?>" title="<?php echo $link->description; ?>" target="<?php echo $link->target; ?>"><?php echo $link->name; ?></a></li>
		<?php endforeach; ?>
</ul>
<?php endif; ?>

