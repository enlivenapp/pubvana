<p><a class="btn btn-default btn-sm" href="<?php echo site_url('admin_navigation/add_nav') ?>"><?php echo lang('index_add_new_nav') ?></a></p>

<div>
    <!-- Nav tabs -->
    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="active"><a href="#home" aria-controls="home" role="tab" data-toggle="tab"><?= lang('tab_all_nav_items') ?></a></li>
        <li role="presentation"><a href="#redirects" aria-controls="redirects" role="tab" data-toggle="tab"><?= lang('tab_nav_redirects') ?></a></li>
    </ul>

    <!-- Tab panes -->
    <div class="tab-content">
        <div role="tabpanel" class="tab-pane fade in active" id="home">
            <ul class="list-group m-t-l" id="sortable">
                <?php foreach ($navs as $nav): ?>
                    <li class="list-group-item" id="item-<?= $nav->id ?>">
                        <i class="fa fa-arrows" aria-hidden="true"></i>
                        <?= $nav->title ?>

                        <div class="pull-right">
                            <a href="<?= site_url($nav->url) ?>" target="_blank" class="btn btn-default btn-xs">View</a> 
                            <a href="<?= site_url('admin_navigation/edit/' . $nav->id) ?>" class="btn btn-default btn-xs">Edit</a> 
                            <a id="remove-nav-<?= $nav->id; ?>" href="<?= site_url('admin_navigation/remove_nav/' . $nav->id) ?>" class="btn btn-danger btn-xs">Delete</a> 
                        </div>
                    
                        

                        

                <script>
                    $('a#remove-nav-<?= $nav->id ?>').confirm({
                        title: 'Please Confirm',
                        content: "Are you sure you want to remove <b><?= $nav->title ?></b> from Navigation?<br><br><b>This action can not be undone and does NOT delete the page itself.</b>",
                        theme: 'supervan'
                    });

                </script>

                    <?php if ($nav->children): ?>
                        
                        <ul class="list-group m-t-l">
                            <?php foreach ($nav->children as $child): ?>
                                <li class="list-group-item" id="item-<?= $child->id ?>">
                                    <i class="fa fa-arrows" aria-hidden="true"></i>
                                    <?= $child->title ?>
                                    
                                    <div class="pull-right">
                                        <a href="<?= site_url($child->url) ?>" target="_blank" class="btn btn-default btn-xs">View</a> 
                                        <a href="<?= site_url('admin_navigation/edit/' . $child->id) ?>" class="btn btn-default btn-xs">Edit</a> 
                                        <a id="remove-nav-<?= $child->id; ?>" href="<?= site_url('admin_navigation/remove_nav/' . $child->id) ?>" class="btn btn-danger btn-xs">Delete</a> 
                                    </div>

                                    <script>
                                        $('a#remove-nav-<?= $child->id ?>').confirm({
                                            title: 'Please Confirm',
                                            content: "Are you sure you want to remove <b><?= $child->title ?></b> from Navigation?<br><br><b>This action can not be undone and does NOT delete the page itself.</b>",
                                            theme: 'supervan'
                                        });

                                    </script>
                                </li>

                            <?php endforeach ?>
                        </ul>
                    <?php endif ?>

                    </li>
                <?php endforeach ?>
            </ul>

            <script>
                $('#sortable').nestedSortable({
                    items: 'li',
                    maxLevels: '2',
                    listType: 'ul',
                    tabSize: '35',
                    placeholder: "ui-state-highlight",
                    update: function (event, ui) {
                        var data = $(this).sortable('serialize');
                        $.ajax({
                            data: data,
                            type: 'POST',
                            url: '<?= site_url("admin_navigation/update_nav_order") ?>',
                        });
                    }
                });
            </script>
            <p class="m-t-l"><?= lang('index_nav_desc') ?></p>
        </div>
        <div role="tabpanel" class="tab-pane fade" id="redirects">
            <p class="m-t-l"><?= lang('index_redirect_desc') ?></p>

            <?php if ( ! $redirects ): ?>
            <h4 class="text-center"><?= lang('nav_no_redirects_found') ?></h4>
            <?php else: ?>
            <table class="table table-condensed">
                <tr>
                    <th>From</th>
                    <th>To</th>
                    <th>Type</th>
                    <th>HTTP Redirect Type</th>
                    <th></th>
                </tr>
                <?php foreach ($redirects as $redir): ?>
                    <tr>
                    <td><?= $redir->old_slug ?></td>
                    <td><?= $redir->new_slug ?></td>
                    <td><?= $redir->type ?></td>
                    <td><?= $redir->code ?></td>
                    <td class="text-right">
                        <a href="<?= site_url('admin_navigation/edit_redirect/' . $redir->id) ?>" class="btn btn-default btn-xs"><?= lang('redir_edit_btn') ?></a>
                        <a id="remove-redirect-<?= $redir->id ?>" href="<?= site_url('admin_navigation/remove_redirect/' . $redir->id) ?>" class="btn btn-danger btn-xs"><?= lang('redir_remove_btn') ?></a>

                        <script>
                    $('a#remove-redirect-<?= $redir->id ?>').confirm({
                        title: 'Please Confirm',
                        content: "Are you sure you want to remove the <b><?= $redir->old_slug ?></b> Redirect? <br><br><b>This action can not be undone.</b>",
                        theme: 'supervan'
                    });

                </script>

                    </td>
                </tr>
              <?php endforeach ?>
            </table>

            <?php endif ?>
        </div>
    </div>

</div>
