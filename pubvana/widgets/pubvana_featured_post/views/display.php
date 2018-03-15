		<div class="card mx-auto" style="width: 18rem;">
    			<div class="badger-right badger-warning" data-badger="<?= lang('featured') ?>"></div>
			<?php if ($post->feature_image): ?>
		  		<img class="card-img-top" src="<?= base_url('uploads/' . $post->feature_image) ?>" alt="<?= $post->title ?>">
			<?php endif ?>
		  <div class="card-body">
		    <h5 class="card-title"><?= $post->title ?></h5>
		    <p class="card-text"><?= $post->excerpt ?></p>
		    <a href="<?= post_url($post->url_title) ?>" class="btn btn-outline-default"><?= lang('btn_read_more') ?></a>
		  </div>
		</div>
