<div class="col-sm-12" id="recent"> 

  <a class="btn btn-default pull-right" href="<?= site_url('search') ?>"><?= lang("search_again") ?></a>
  <div class="page-header text-muted">
  	<?= $page['title'] ?>
  </div> 
  
</div>

<?php if ($results): ?>

	<?php foreach ($results as $item): ?>

		<div class="row"> 
		<div class="col-sm-1">
			<i class="hidden-xs glyphicon glyphicon-chevron-right" aria-hidden="true"></i>
		</div>

	      <div class="col-sm-4">

	      	<p><b><a href="<?= $item->link ?>" title="<?= $item->title ?>"><?= $item->title ?></a></b><br><?= $item->date_display ?></p>
	      </div>
	      <div class="col-sm-6">
			<p><?= $item->content ?></p>
			</div>
			<div class="col-sm-1">						
            <span class="plus"><a href="<?= $item->link ?>" title="Lorem ipsum"><i class="glyphicon glyphicon-plus"></i></a></span>

	      </div>
	  </div>
	  <hr>
	<?php endforeach ?>

	<?php else: ?>
  		<h3 class="text-center"><?= lang('no_results') ?></h3>

<?php endif ?>
