<?php if (! $curlInstalled): ?>
  <div class="alert alert-danger" role="alert"><?= lang('updates_curl_not_avail') ?></div>
<?php endif ?>

<?php if (! $zipArchive): ?>
  <div class="alert alert-danger" role="alert"><?= lang('updates_zip_not_avail') ?></div>
<?php endif ?>


<?php if ($update_avail && $update_avail['status'] == 'failed'): ?>
  <h4 class="text-center text-danger"><?= $update_avail['message'] ?></h4>

<?php elseif ($update_avail['current_version']): ?>

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


<div class="row" style="margin-top: 10px;">
  	<div class="col-xs-12">
  		<div class="text-center">
  			<?php if ($this->config->item('pv_version') == $update_avail['current_version']): ?>
    		<div class="alert alert-success" role="alert">
    			<?= lang('updates_install_up_to_date_text') ?>
    			
    		</div>
    		<?php elseif ($this->config->item('pv_version') < $update_avail['current_version']): ?>
 
    		<div class="alert alert-warning" role="alert">
    			<?= lang('updates_update_available') ?>
    		</div>
  		</div>
    
		<div class="row">
	 		<div class="col-md-6">
	  			<div class="col-md-12 text-center">
	  				<h2><?= lang('updates_auto_hdr') ?></h2>
	  				<?php if (! $curlInstalled || ! $zipArchive): ?>
	  					<?= lang('updates_disabled_txt') ?>
	  				<?php else: ?>
	      				<a id="auto-update" href="<?= site_url('admin_updates/auto_update') ?>" class="btn btn-lg btn-default"><?= lang('updates_update_now_btn') ?></a>
	      				<p><?= lang('updates_auto_txt') ?></p>
                <script>
                    $('a#auto-update').confirm({
                        title: 'Please Confirm',
                        content: "<?=lang('updates_auto_confirm_txt') ?>",
                        theme: 'supervan'
                    });

                </script>
	      			<?php endif ?>
	    		</div>
	  		</div>


	  		<div class="col-md-6">
	  			<div class="col-md-12 text-center">
	  				<h2><?= lang('updates_download_hdr') ?></h2>
	      			<a href="https://pubvana.org/page/downloads" target="_blank" class="btn btn-lg btn-default"><?= lang('updates_download_btn') ?></a>
	      		</div>
	      		<p><?= lang('updates_download_txt') ?></p>
	  		</div>
		</div>

    	<?php endif ?> 
  	</div>
</div>

<?php else: ?>
  <div class="row">
    <div class="col-xs-12 text-center bg-primary">
      <h4><?= lang('updates_pv_install_text') ?></h4>
      <h3><?= $this->config->item('pv_version') ?></h3>
    </div>
</div>

<?php endif ?>

