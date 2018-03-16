<?php $this->load->view('header.php'); ?>

	<div class="row">
		<div class="col-sm-8 col-xs-12 col-sm-offset-2">
			<h2>Welcome</h2>
			<p class="lead">Pubvana is a free and open source blog and small business CMS. It provides users with a very powerful yet easy to use interface which makes blogging and website content management simple and enjoyable.</p>
		</div>
	</div>
	
	<div class="row">
		<div class="col-sm-6 container-bg" style="height: 125px;">
			<h4>Prerequisites:</h4>
			<ul class="text-left">
				<li>MySQL/MariaDB database with database name, hostname, username, and password</li>
				<li>PHP 5.6 or higher</li>
				<li>Writable Directories: /application/config, /uploads, / (root dir for .htaccess)</li>
			</ul>

		</div>
		<div class="col-sm-6 container-bg" style="height: 125px;">
			<h4>Optional:</h4>
			<ul class="text-left">
				<li>PHP cURL module - Recommended</li>
				<li>mod_rewrite (for Apache users) for prettier URLs - Recommended</li>
				<li><a href="https://getcomposer.org" target="_blank">Composer installed</a> - Recommended</li>
				<li>SSH Shell access to your server/account (Composer Updates) - Recommended</li>
			</ul>

		</div>		
	</div>

	<div class="row">
		<div class="col-sm-6 container-bg">
			<h4>Environment Found:</h4>
			<table class="table table-condensed table-responsive">
				<tr>
					<th>Server Type:</th>
					<td><?= ucfirst($server_type) ?> (<?php echo ($mod_rewrite == 1) ? 'with mod_rewrite' : 'with OUT mod_rewrite'; ?>)</td>
				</tr>
				<tr>
					<th>PHP Version Installed:</th>
					<td class="<?php echo (phpversion() < "5.6.0") ? 'text-warning' : '';  ?>"><?= phpversion() ?> <?php echo (phpversion() >= "5.6.0") ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>';  ?></td>
				</tr>
				<tr>
					<th>cURL Installed:</th>
					<td><?php echo ($curl_available == 1) ? 'Yes!' : 'No'; ?></td>
				</tr>
				<tr>
					<th>PHP Info:</th>
					<td><a href="index.php/installer/view_php_info" target="_blank">View PHP_INFO</a></td>
				</tr>
			</table>
			<p>Note: Verify these results with your hosting provider.</p>

		</div>
		<div class="col-sm-6 container-bg" style="height: 200px;">
			<h4>File System: (writable)</h4>
			<p>These folders must be readable and writable.</p>
			<ul class="text-left">
				<li>/pubvana/Config : <?php echo ($config_dir) ? 'Yes <i class="fa fa-check text-success"></i>' : 'No <i class="fa fa-times text-danger"></i>'; ?></li>
				<li>/pubvana/cache/sessions : <?php echo ($cache_sess_dir) ? 'Yes <i class="fa fa-check text-success"></i>' : 'No <i class="fa fa-times text-danger"></i>'; ?></li>
				<li>/pubvana/cache/assets : <?php echo ($config_assets_dir) ? 'Yes <i class="fa fa-check text-success"></i>' : 'No <i class="fa fa-times text-danger"></i>'; ?></li>
				<li>/uploads : <?php echo ($uploads_dir) ? 'Yes <i class="fa fa-check text-success"></i>' : 'No <i class="fa fa-times text-danger"></i>'; ?></li>
				<li>(project root (/) : <?php echo ($home_dir) ? 'Yes <i class="fa fa-check text-success"></i>' : 'No <i class="fa fa-times text-danger"></i>'; ?></li>
			</ul>

		</div>		
	</div>
		
	<div class="row" style="margin-top: 20px;">
		<div class="col-sm-8 col-xs-12 col-sm-offset-2">
			<p>If at any point you get stuck please ask your web hosting provider or <a href="http://open-blog.org">contact us</a> for support or see our options to install for you.</p>
			<p><a href="index.php/installer/step_one" class="btn btn-lg btn-default">Begin Installation</a></p>
		</div>
	</div>
<?php $this->load->view('footer.php'); ?>
