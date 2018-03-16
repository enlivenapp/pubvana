<h2><?= lang('theme_options_hdr') ?></h2>
<p><?= lang('theme_options_subheader') ?></p>

<div class="row" style="background-color: #FAFAFA; margin: 1em 0; padding: 1em 0; border: solid 1px #CCC; border-radius: 8px;">
	<div class="col-sm-6 text-center">
		<p><?= lang('theme_options_for_txt') ?> <b><em><?= $theme->name ?></em></b><br><?= $theme->description ?></p>
	</div>
	<div class="col-sm-6 text-center">
		<p><?= $theme->author ?><br><?= $theme->author_email ?><br></p>
	</div>
</div>


<div class="row">
	<div class="col-xs-12">
		<form id="contact-form" class="form-horizontal" method="post" action="<?= current_url(); ?>">
			<?php foreach ($options as $opt): ?>
			  <div class="form-group">
			    <label for="" class="col-sm-2 control-label"><?= humanize($opt->name) ?></label>
			    <div class="col-sm-10">
			      <input type="text" class="form-control" id="<?= $opt->id ?>" name="<?= $opt->name ?>" placeholder="<?= $opt->name ?>" value="<?= $opt->value ?>" required>
			    </div>
			  </div>
			<?php endforeach ?>

		  <div class="form-group">
		    <div class="col-sm-offset-2 col-sm-10 text-right">
		      <button type="submit" class="btn btn-default"><?= lang('theme_options_save_btn') ?></button>
		    </div>
		  </div>
		</form>
	</div>
</div>


<div class="row">
	<div class="col-xs-12">
		<p><?= lang('theme_options_images_txt') ?></p>
	</div>
</div>
