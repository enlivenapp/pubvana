<div class="col-sm-12" id="recent">   
  <div class="page-header text-muted">
  	<?= $page['title'] ?>
  </div> 
</div>

<p><?= lang('search_txt') ?></p>

<?= validation_errors(); ?>


<form class="form" action="<?= site_url('search')?>" method="post">
  <div class="input-group input-group-lg">
  <input name="search_term" type="text" class="form-control" aria-label="Search">
  <div class="input-group-btn">
    <button type="submit" class="btn btn-primary"><?= lang('search_btn') ?></button>
  </div>
</div>

<div class="row" style="margin-top: .8em;">
	
	<div class="col-md-6 text-center">
		
		<p><?= lang('search_in') ?></p>
		<div data-toggle="buttons">
                <div class="btn-group">
					<label class="btn btn-default">
					  <input class="sr-only" type="radio" name="search_in" id="search-in-2" value="pages" required> <?= lang('pages') ?>
					</label>
					<label class="btn btn-default active">
					  <input class="sr-only" type="radio" name="search_in" id="search-in-3" value="posts" required checked> <?= lang('posts') ?>
					</label>
				</div>
			</div>


	</div>

	<div class="col-md-6 text-center">
		<p><?= lang('search_in') ?></p>
		<div data-toggle="buttons">
            <div class="btn-group">

				<label class="btn btn-default active">
				  <input class="sr-only" type="radio" name="titlebody" id="titlebody-1" value="both" checked> <?= lang('title') ?> &amp; <?= lang('body') ?> 
				</label>
				<label class="btn btn-default">
				  <input class="sr-only" type="radio" name="titlebody" id="titlebody-2" value="title"> <?= lang('title') ?>
				</label>
				<label class="btn btn-default">
				  <input class="sr-only" type="radio" name="titlebody" id="titlebody-3" value="body"> <?= lang('body') ?>
				</label>
			</div>
		</div>
	</div>


</div>

</form>

