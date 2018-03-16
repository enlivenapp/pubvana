<p><?= lang('themes_subheader') ?></p>
<div class="row">

<?php foreach($themes as $theme): ?>
  <div class="col-sm-6 col-md-4">
    <div class="thumbnail" >
      <img style="" src="<?= base_url('pubvana/themes/' . $theme->path . '/' . $theme->image) ?>" alt="<?= $theme->description ?>">
      <div class="caption">
        <h3><?= $theme->name ?></h3>
        <p><?= $theme->description ?></p>
        <p><?= lang('theme_author_title') ?>: <?= $theme->author ?></p>
        <p><?= lang('theme_author_email') ?>: <?= $theme->author_email ?></p>
         <p><?= lang('themes_theme_type_desc') ?>: <?php echo ($theme->is_admin == 1) ? lang('themes_type_admin') : lang('themes_type_front')  ?></p>
        <p><?= lang('theme_version') ?>: <?= $theme->version ?></p>
        <p><b><?php echo ($theme->is_active == 1) ? lang('themes_theme_in_use') : lang('themes_theme_not_in_use')  ?></b></p>
       
        <p>
          <?php if ($theme->is_active == 0): ?> <a href="<?= site_url('admin_themes/activate/' . $theme->id) ?>" class="btn btn-default"><?= lang('themes_activate_theme') ?></a> <?php endif ?>
          <?php if ($theme->has_options == 1): ?> <a href="<?= site_url('admin_themes/options/' . $theme->id) ?>" class="btn btn-default"><?= lang('themes_theme_options') ?></a> <?php endif ?>
        </p>
      </div>
    </div>
  </div>

  <?php endforeach ?>
</div>
