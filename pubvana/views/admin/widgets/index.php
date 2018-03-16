<!-- <p><?= lang('widgets_hdr_txt') ?></p> -->


<div class="row">

	<div class="col-sm-4">
		<h4><?= lang('avail_widgets_hdr') ?></h4>
		<hr>
		<?php if (! $widgets_list): ?>
			<div class="text-center"><h4><?= lang('no_widgets_found') ?></h4></div>
		<?php else: ?>
			<ul class="list-group m-t-l">
				<?php foreach ($widgets_list as $widget): ?>
				
                    <li class="list-group-item" id="item-<?= $widget->id ?>">
                        <!-- <i class="fa fa-exchange" aria-hidden="true"></i>-->
                        <?= $widget->name ?> <i class="fa fa-question-circle-o" data-toggle="modal" data-target="#Modal-<?= $widget->id ?>" aria-hidden="true"></i>
                        
                        <div class="pull-right">
						  <div class="btn-group">
						  <button type="button" class="btn btn-default btn-xs"><?= lang('widgets_add_to') ?></button>
						  <button type="button" class="btn btn-default dropdown-toggle btn-xs" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						    <span class="caret"></span>
						    <span class="sr-only">Toggle Dropdown</span>
						  </button>
						  <ul class="dropdown-menu">
						  	<?php foreach ($widgets['widget_areas'] as $area): ?>
						    	<li><a href="<?= site_url('admin_widgets/add/' . $widget->id . '/' . $area->id) ?>"><?= ucwords(humanize($area->name)) ?></a></li>
							<?php endforeach ?>
						  </ul>
						</div>
                        </div>
                    </li>
					<div class="modal fade" id="Modal-<?= $widget->id ?>" tabindex="-1" role="dialog" aria-labelledby="ModalLabel-<?= $widget->id ?>">
					  <div class="modal-dialog" role="document">
					    <div class="modal-content">
					      <div class="modal-header">
					        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					        <h4 class="modal-title" id="myModalLabel"><?= $widget->name ?></h4>
					      </div>
					      <div class="modal-body">
					        <p><?= $widget->description ?></p>
					        <p><?= $widget->author ?></p>
					        <p><?= $widget->author_email ?></p>
					        <p><?= $widget->author_website ?></p>
					        <p>v<?= $widget->version ?></p>
					      </div>
					      <div class="modal-footer">
					        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
					      </div>
					    </div>
					  </div>
					</div>
				<?php endforeach ?>
			</ul>
		<?php endif ?>
	</div>


	<div class="col-sm-8">
		
		<?php if ($widgets['widget_areas']): ?>

			<?php foreach ($widgets['widget_areas'] as $area): ?>

				<div class="widget-area widget-area-default">
					<div class="widget-area-heading">
						<h3 class=""><?= ucwords(humanize($area->name)) ?></h3>
					</div>

					<div class="widget-area-body">
						

				  	<?php if (!$area->active_widgets): ?>

				  		<div class="text-center"><h4><?= lang('no_widgets_added_yet') ?></h4></div>
				  	<?php else: ?>
						<div class="sortable">
				  		<?php foreach ($area->active_widgets as $instance): ?>

				  			<div class="panel panel-default widget-instance" id="widget-instance-container-<?= $instance->id ?>">
								<div class="panel-heading collapsable">
									<h3 class="panel-title"><i class="fa fa-arrows-v" aria-hidden="true"></i><em>(<?= $instance->widget_name ?>)</em> <?= $instance->title ?> </h3>
									<span class="pull-right" id = "widget-instance-header-<?= $instance->id ?>">
										<em><?= ($instance->active == 1) ? lang('widget_active_radio_txt') : lang('widget_inactive_radio_txt') ; ?></em> &nbsp;&nbsp;
										<i class="glyphicon glyphicon-chevron-down"></i>
									</span>
								</div>
								<div class="panel-body" id = "widget-instance-body-<?= $instance->id ?>">
									<?= form_open('admin_widgets/update_instance', ['class' => 'form-horizontal'], ['widget_instance' => $instance->id]) ?>

									<!-- title -->
									<div class="form-group">
									    <label for="widget_title" class="col-sm-2 control-label">Title</label>
									    <div class="col-sm-10">
									      	<?= form_input(['name' => 'title', 'id' => 'widget_title', 'value' => $instance->title, 'class' => 'form-control']) ?>
									    </div>
									 </div>

									 <!-- show title? -->
									 <div class="form-group">
									    <label for="show_title" class="col-sm-2 control-label">Show Title</label>
									    <div class="col-sm-10">
											<label class="checkbox-inline">
												<?= form_radio(['name' => 'show_title', 'id' => 'show_title_yes', 'value' => '1', 'checked' => ($instance->show_title == 1) ? true : false]) ?>	<?= lang('yes') ?>
											</label> 
								
											<label class="checkbox-inline">
												<?= form_radio(['name' => 'show_title', 'id' => 'show_title_no', 'value' => '0', 'checked' => ($instance->show_title == 0) ? true : false]) ?>	<?= lang('no') ?>	
											</label>
									    </div>
									 </div>

									 <!-- Options -->
									 <?php if ($instance->options): ?>
									 	<div class="form-group">
									    <label for="widget_title" class="col-sm-2 control-label"><?= lang('widgets_options_label_hdr') ?></label>
									    <div class="col-sm-10">

									    	<?php foreach ($instance->options as $optk => $option): ?>
									    		<div class="row">
									    			<div class="col-xs-4">
									    				<label><?= $option['label'] ?></label>
									    			</div>
									    			<div class="col-xs-8">
									    				<?= ($option['help_text']) ? '<p class="help-block">' . $option['help_text'] . '</p>' : '' ?>

									    				<?php
									    				if ($option['field_type'] == 'text')
									    				{
									    					echo form_input(['name' => "options[$optk]", 'id' => $optk, 'value' => $option['default'], 'class' => 'form-control']);
									    					//echo $this->pvcore->build_form_field($option['field_type'], $optk, $option['default'], $option['options']);
									    				}
									    				elseif ($option['field_type'] == 'dropdown')
									    				{
									    					$option['options'] = explode('|', $option['options']);
									    					foreach ($option['options'] as $field_opts)
									    					{
									    						$dropdown_opts[$field_opts] = $field_opts;
									    					}
									    					echo form_dropdown("options[$optk]", $dropdown_opts, $option['default'], ['class' => 'form-control']);
									    				}
									    				?>
									    			</div>
									    		</div>
									    		<hr>
									    	<?php endforeach ?>
									    </div>
									 </div>

									 <?php endif ?>
									 <!-- content -->
									 <div class="form-group">
									    <label for="widget_title" class="col-sm-2 control-label">Content</label>
									    <div class="col-sm-10">
									    	<p class="help-block"><?= lang('widget_content_txt') ?></p>
									      	<?= form_textarea(['name' => 'content', 'id' => 'widget_content', 'value' => $instance->content, 'class' => 'form-control']) ?>
									    </div>
									 </div>

									 <!-- is it active? -->
									 <div class="form-group">
									    <label for="show_title" class="col-sm-2 control-label">Active</label>
									    <div class="col-sm-10">
									    	<p class="help-block"><?= lang('widget_active_txt') ?></p>
											<label class="checkbox-inline">
												<?= form_radio(['name' => 'active', 'id' => 'widget_active_yes', 'value' => '1', 'checked' => ($instance->active == 1) ? true : false]) ?>	<?= lang('widget_active_radio_txt') ?>
											</label> 
								
											<label class="checkbox-inline">
												<?= form_radio(['name' => 'active', 'id' => 'widget_active_no', 'value' => '0', 'checked' => ($instance->active == 0) ? true : false]) ?>	<?= lang('widget_inactive_radio_txt') ?>	
											</label>
									    </div>
									 </div>
									 <!-- BUTTOOOOONS -->
									<div class="form-group">
										<div class="col-sm-offset-2 col-sm-10">
											<button type="submit" class="btn btn-default">Save</button> 
											<a id="remove-instance-<?= $instance->id ?>" href="<?= site_url('admin_widgets/remove_instance/' . $instance->id) ?>" class="btn btn-danger">Remove Widget</a>

											<script>
							                    $('a#remove-instance-<?= $instance->id ?>').confirm({
							                        title: 'Please Confirm',
							                        content: "Are you sure you want to remove the <b><?= $instance->title ?></b> from <b><?= ucwords(humanize($area->name)) ?></b>?<br><br><b>This action can not be undone.</b>",
							                        theme: 'supervan'
							                    });

							                </script>

										</div>
									</div>
									
									<?= form_close() ?>

								<script type="text/javascript">
									$("#widget-instance-body-<?= $instance->id ?>").hide();
									$("#widget-instance-header-<?= $instance->id ?>").addClass('panel-collapsed');

									$(document).on('click', '#widget-instance-header-<?= $instance->id ?>', function(e){
								    var $this = $(this);
									if(!$this.hasClass('panel-collapsed')) 
									{
										$this.parents('.panel').find('.panel-body').slideUp();
										$this.addClass('panel-collapsed');
										$this.find('i').removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');
									} 
									else 
									{
										$this.parents('.panel').find('.panel-body').slideDown();
										$this.removeClass('panel-collapsed');
										$this.find('i').removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
									}
								})
								</script>

								</div>
							</div>

				  		<?php endforeach ?>
						</div>			  		

				  	<?php endif ?>
				    
					</div>
				</div>
				
				  		
			<?php endforeach ?>

		<?php endif ?>
	</div>

</div>

<script type="text/javascript">
	$(function () {
    $(".sortable").sortable({
    	placeholder: "ui-state-highlight",
    	update: function (event, ui) {
            var data = $(this).sortable('serialize');
            $.ajax({
                data: data,
                type: 'POST',
                url: '<?= site_url("admin_widgets/update_instance_order") ?>'
            });
        }
    });

    $(".sortable").disableSelection();
});
</script>
