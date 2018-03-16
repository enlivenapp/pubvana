<div class="col-sm-12" id="recent">   
  <div class="page-header text-muted">
  	<?= $page['title'] ?>
  </div> 
</div>

<p><?= lang('contact_txt') ?></p>

<?= validation_errors(); ?>

<link rel="stylesheet" href="http://cdnjs.cloudflare.com/ajax/libs/jquery.bootstrapvalidator/0.5.2/css/bootstrapValidator.min.css"/>
<script type="text/javascript" src="http://cdnjs.cloudflare.com/ajax/libs/jquery.bootstrapvalidator/0.5.2/js/bootstrapValidator.min.js"></script>

<div class="container">
	<div class="row">
		<form role="form" id="contact-form" class="contact-form" method="post" action="<?= site_url('contact')?>">
                    <div class="row">
                		<div class="col-md-6">
                  		<div class="form-group">
                            <input type="text" class="form-control" name="Name" autocomplete="off" id="Name" placeholder="Name" required autofocus>
                  		</div>
                  	</div>
                    	<div class="col-md-6">
                  		<div class="form-group">
                            <input type="email" class="form-control" name="email" autocomplete="off" id="email" placeholder="E-mail" required>
                  		</div>
                  	</div>
                  	</div>
                  	<div class="row">
                  		<div class="col-md-12">
                  		<div class="form-group">
                            <textarea class="form-control textarea" rows="3" name="Message" id="Message" placeholder="Message" required></textarea>
                  		</div>
                  	</div>
                    </div>
                    <div class="row">
                    <div class="col-md-12">
                    	<?php if ($this->config->item('use_recaptcha') == 1): ?>
						  <div class="form-group">
							  <script src='https://www.google.com/recaptcha/api.js'></script>
							  <div class="g-recaptcha" data-sitekey="<?php echo $this->config->item('recaptcha_site_key') ?>"></div>
						  </div>
							<?php endif ?>

							<?php if ($this->config->item('use_honeypot') == 1): ?>
							<div style="position: absolute; left: -999em;">
								<input name="date_stamp_gotcha" id="date_stamp_gotcha" type="text" value="" class="form-control">
							</div>
							<?php endif ?>
                  <button type="submit" class="btn btn-default pull-right">Send a message</button>
                  </div>
                  </div>
                </form>
	</div>
</div>

<script type="text/javascript">

$('#contact-form').bootstrapValidator({
//        live: 'disabled',
        message: 'This value is not valid',
        feedbackIcons: {
            valid: 'glyphicon glyphicon-ok',
            invalid: 'glyphicon glyphicon-remove',
            validating: 'glyphicon glyphicon-refresh'
        },
        fields: {
            Name: {
                validators: {
                    notEmpty: {
                        message: '<?= lang('name_empty'); ?>'
                    }
                }
            },
            email: {
                validators: {
                    notEmpty: {
                        message: '<?= lang('email_empty'); ?>'
                    },
                    emailAddress: {
                        message: '<?= lang('email_invalid'); ?>'
                    }
                }
            },
            Message: {
                validators: {
                    notEmpty: {
                        message: '<?= lang('msg_empty'); ?>'
                    }
                }
            }
        }
    });
</script>
