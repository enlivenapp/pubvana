<div class="row">
    <div class="col-md-9">
        <div class="row text-center">
            <div class="dashbord">
            <div class="icon-section">
                <i class="fa fa-pencil" aria-hidden="true"></i><br>
                <small><?= lang('dash_published_posts') ?></small>
                <p><?= $post_count ?></p>
            </div>
            <div class="detail-section">
                <a href="<?= site_url('admin_posts') ?>"><?= lang('more') ?></a>
            </div>
        </div>
        <div class="dashbord">
            <div class="icon-section">
                <i class="fa fa-comments" aria-hidden="true"></i><br>
                <small><?= lang('dash_comments') ?></small>
                <p><?php if ($this->config->item('comment_system') == 'fb'): ?>
                <small><?= lang('dash_using_fb_comment_system') ?></small>
            <?php else: ?>
                <?= number_format($active_comments_count) ?>  / <?= number_format($modded_comments_count) ?>
                <?php endif ?>  
                </p>
            </div>
            <div class="detail-section">
                <a href="<?= site_url('admin_comments') ?>"><?= lang('more') ?></a>
            </div>
        </div>
        <div class="dashbord">
            <div class="icon-section">
                <i class="fa fa-users" aria-hidden="true"></i><br>
                <small><?= lang('dash_content_subscribers') ?></small>
                <p><?= number_format($notification_count) ?></p>
            </div>
            <div class="detail-section">
                <a href="#">&nbsp;</a>
            </div>
        </div>
        <div class="dashbord">
            <div class="icon-section">
                <i class="fa fa-eye-slash" aria-hidden="true"></i><br>
                <small><?= lang('dash_post_drafts') ?></small>
                <p><?= number_format($post_draft_count) ?></p>
            </div>
            <div class="detail-section">
                <a href="<?= site_url('admin_posts') ?>"><?= lang('more') ?></a>
            </div>
        </div>
        <div class="dashbord">
            <div class="icon-section">
                <i class="fa fa-eye" aria-hidden="true"></i><br>
                <small><?= lang('dash_total_post_views') ?></small>
                <p><?= number_format($total_post_views_count) ?></p>
            </div>
            <div class="detail-section">
                <a href="<?= site_url('admin_posts') ?>"><?= lang('more') ?></a>
            </div>
        </div>
        <div class="dashbord">
            <div class="icon-section">
                <i class="fa fa-user-circle" aria-hidden="true"></i><br>
                <small><?= lang('users_hdr') ?></small>
                <p><?= number_format($users_count) ?></p>
            </div>
            <div class="detail-section">
                <a href="<?= site_url('admin_users') ?>"><?= lang('more') ?></a>
            </div>
        </div> <!-- /row -->

        <hr>

        <div class="row">
            <div class="col-md-6">
                <h4><?= lang('dash_settings_checklist') ?></h4>
                <div class="panel panel-default">
                        
                        <div class="panel-body">
                            <p><a href="<?= site_url('admin/settings') ?>" class="btn btn-default btn-sm"><?= lang('dash_goto_settings') ?></a></p>
                            <ul class="list-group text-left">
                                <li class="list-group-item">
                                    <span class="badge"><i class="fa fa-<?= ($this->config->item('gAnalyticsPropId')) ? 'check' : 'exclamation';  ?>"></i></span>
                                    <?= lang('dash_google_analytics_conn') ?>
                                </li>
                                <li class="list-group-item">
                                    <span class="badge"><i class="fa fa-<?= ($this->config->item('recaptcha_site_key')) ? 'check' : 'exclamation';  ?>"></i></span>
                                    <?= lang('dash_google_recaptcha') ?>
                                </li>
                                <li class="list-group-item">
                                    <span class="badge"><i class="fa fa-<?= ($this->config->item('use_recaptcha')) ? 'check' : 'exclamation';  ?>"></i></span>
                                    <?= lang('dash_use_google_recaptcha') ?> <?= ($this->config->item('use_recaptcha')) ? '(' . lang('dash_enabled') . ')' : '(' . lang('dash_disabled') . ')';  ?>
                                </li>
                                <li class="list-group-item">
                                    <span class="badge"><i class="fa fa-<?= ($this->config->item('use_honeypot')) ? 'check' : 'exclamation';  ?>"></i></span>
                                    <?= lang('dash_use_honeypot') ?> <?= ($this->config->item('use_honeypot')) ? '(' . lang('dash_enabled') . ')' : '(' . lang('dash_disabled') . ')';  ?>
                                </li>
                            </ul>
                                
                        </div>
                    </div>
                

            </div>


            <div class="col-md-6">
                <h4><?= lang('dash_settings_quick_look') ?></h4>
                <div class="panel panel-default">
                        
                        <div class="panel-body">
                            <p><a href="<?= site_url('admin/settings') ?>" class="btn btn-default btn-sm"><?= lang('dash_goto_settings') ?></a></p>
                            <ul class="list-group text-left">
                                <li class="list-group-item">
                                    <span class="badge"><?= $this->config->item('admin_email') ?></span>
                                    <?= lang('admin_email_label') ?>
                                </li>
                                <li class="list-group-item">
                                    <span class="badge"><?= ($this->config->item('comment_system') ==  'fb') ? 'Facebook' : 'Local';  ?></span>
                                    <?= lang('dash_comment_system_used') ?>
                                </li>
                                <li class="list-group-item">
                                    <span class="badge"><i class="fa fa-<?= ($this->config->item('allow_registrations')) ? 'check' : 'exclamation';  ?>"></i></span>
                                    <?= lang('allow_registrations_label') ?> 
                                </li>
                                <li class="list-group-item">
                                    <span class="badge"><i class="fa fa-<?= ($this->config->item('email_activation')) ? 'check' : 'exclamation';  ?>"></i></span>
                                    <?= lang('email_activation_label') ?>
                                </li>
                            </ul>
                                
                        </div>
                    </div>
            </div>

        </div>
        

    </div>
</div>






    <div class="col-md-3">
        <!-- last Pubvana News -->
        <div class="row">
            <div class="col-xs-12">
                <h3><?= lang('pubvana_news_hdr') ?></h3>
                <?php if ($news): ?>
                    <?php foreach ($news as $item): ?>
                    <div class="panel panel-default">
                        <div class="panel-heading panel-heading-blue">
                            <h3 class="panel-title"><a href="https://pubvana.org/blog/<?= $item->url_title ?>" target="_blank"><?= $item->title ?></a> <small class="pull-right"><?= $item->date_posted ?></small></h3>
                        </div>
                        <div class="panel-body">
                                <?php if ($item->feature_image_url): ?> 
                                    <img class="img-responsive" src="<?= $item->feature_image_url ?>">
                                <?php endif ?>
                                <p><?= $item->excerpt ?></p>
                                <p class="text-right"><a class="btn btn-default" href="https://pubvana.org/blog/<?= $item->url_title ?>" target="_blank"><?= lang('more') ?> <i class="fa fa-external-link" aria-hidden="true"></i></a></p>
                        </div>
                    </div>
                    <?php endforeach ?>

                <?php else: ?>
                <h3 class="text-center"><?= lang('pubvana_no_news_found') ?></h3>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

