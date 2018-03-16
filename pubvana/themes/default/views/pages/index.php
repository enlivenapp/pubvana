<div class="col-sm-12" id="recent">   
  <div class="page-header text-muted">
  	<?= $page['title'] ?>
  </div> 
</div>


	<div class="row">    
      <div class="col-sm-12">
        <p><small><span class="glyphicon glyphicon-time" aria-hidden="true"><time class="page-date" datetime="<?= date("D, d M Y H:i:s T", strtotime($page['date'])) ?>"></span> <?= $page['date_display'] ?></small> <?php if ($page['date_modified']): ?>
                (<?= lang('updated') . " " . date("D, d M Y", strtotime($page['date_modified'])); ?>)
              <?php endif ?>
            </p>
        <p><?= $page['content'] ?></p>
        <h4>
          <small class="text-muted">
            <?php if ( $this->pv_auth->is_admin() || $this->pv_auth->in_group('editor') || $this->pv_auth->logged_in() && $this->session->userdata('user_id') == $post->author  ): ?>
            <a class="btn btn-default text-muted" href="<?= site_url('admin_pages/edit_page/' . $page['id']) ?>"><?= lang('btn_edit'); ?></a> 
            <?php endif ?>
            <br>
            
        </small>
      </h4>
      </div>

    </div>
