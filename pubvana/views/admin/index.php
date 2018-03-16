<div class="row">
    <div class="col-md-3 col-sm-6 col-xs-6"> 
        <div class="panel status panel-success">
            <div class="panel-heading">
                <h1 class="panel-title text-center"><?= $post_count ?></h1>
            </div>
            <div class="panel-body text-center">                        
                <strong>Published Posts</strong>
            </div>
        </div>          
    </div>
    <div class="col-md-3 col-sm-6 col-xs-6"> 
        <div class="panel status panel-success">
            <div class="panel-heading">
                <h1 class="panel-title text-center"><?= $active_comments_count ?></h1>
            </div>
            <div class="panel-body text-center">                        
                <strong>Active Comments</strong>
            </div>
        </div>  
    </div>
    <div class="col-md-3 col-sm-6 col-xs-6"> 
        <div class="panel status panel-danger">
            <div class="panel-heading">
                <h1 class="panel-title text-center"><?= $modded_comments_count ?></h1>
            </div>
            <div class="panel-body text-center">                        
                <strong>Comments Awaiting Moderation</strong>
            </div>
        </div>  
    </div>
    <div class="col-md-3 col-sm-6 col-xs-6"> 
        <div class="panel status panel-warning">
            <div class="panel-heading">
                <h1 class="panel-title text-center"><?= $notification_count ?></h1>
            </div>
            <div class="panel-body text-center">                        
                <strong>Subscriptions to New Content</strong>
            </div>
        </div>  
    </div>
</div>





<!-- last OB News -->
<div class="row">
    <div class="col-xs-12 col-sm-6 col-md-9">
    </div>
    <div class="col-xs-12 col-sm-6 col-md-3">
        <h2>Pubvana News</h2>
        <?php if ($news): ?>
            <?php foreach ($news as $item): ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><a href="https://pubvana.org/blog/<?= $item->url_title ?>" target="_blank"><?= $item->title ?></a> <small class="pull-right"><?= $item->date_posted ?></small></h3>
                </div>
                <div class="panel-body">
                        <?php if ($item->feature_image_url): ?> 
                            <img class="img-responsive" src="<?= $item->feature_image_url ?>">
                        <?php endif ?>
                        <p><?= $item->excerpt ?></p>
                        <p><a class="btn btn-default btn-xs" href="https://pubvana.org/blog/<?= $item->url_title ?>" target="_blank">More <i class="fa fa-external-link" aria-hidden="true"></i></a></p>
                </div>
            </div>
            <?php endforeach ?>

        <?php else: ?>
        <h3 class="text-center">No News Found</h3>
        <?php endif ?>
    </div>
</div>
