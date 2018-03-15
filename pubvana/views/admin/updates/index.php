<?php if ($update_avail && $update_avail['status'] == 'failed'): ?>
  <h4 class="text-center text-danger"><?= $update_avail['message'] ?></h4>

<?php else: ?>

<div class="row">
  <div class="col-xs-6 text-center bg-primary">
    <h4><?= lang('updates_pv_install_text') ?></h4>
    <h3><?= $this->config->item('pv_version') ?></h3>
  </div>

  <div class="col-xs-6 text-center bg-info">
    <h4><?= lang('updates_pv_current_stable_text') ?></h4>
    <h3><?= $update_avail['current_version'] ?></h3>
  </div>
</div>


<div class="row">
  <div class="col-xs-12 text-center">
    <?php if ($this->config->item('pv_version') == $update_avail['current_version']): ?>
    <div class="alert alert-success" role="alert"><?= lang('updates_install_up_to_date_text') ?></div>
    <?php elseif ($this->config->item('pv_version') < $update_avail['current_version']): ?>
 
    <div class="alert alert-warning" role="alert"><?= lang('updates_update_available') ?></div>

    <?php endif ?> 
  </div>
</div>



<div class="row">
  <div class="col-xs-12">
      <h2><?= lang('updates_composer_hdr') ?></h2>
      <p><?= lang('updates_composer_txt') ?></p>
  </div>
</div>

  <div class="row">
  <div class="col-xs-12">
      <h2><?= lang('updates_download_hdr') ?></h2>
      <p><?= lang('updates_download_txt') ?></p>
  </div>
</div>



<?php endif ?>
