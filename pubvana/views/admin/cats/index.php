<h2><?= lang('cats_hdr') ?></h2>

<p><a class="btn btn-default btn-sm" href="<?php echo site_url('admin_cats/add_cat') ?>"><?php echo lang('index_add_new_cat') ?></a></p>

<table class="table table-condensed table-hover">
    <tr>
        <th>Name</th>
        <th>URL (slug)</th>
        <th>Description</th>
        <th></th>
    </tr>
    <?php foreach ($cats as $cat): ?>
        <tr>
        <td><?= $cat->name ?></td>
        <td><a href="<?= site_url('blog/category/' . $cat->url_name) ?>" target="_blank"><?= $cat->url_name ?></a></td>
        <td><?= $cat->description ?></td>
        <td class="text-right">
            <a href="<?= site_url('admin_cats/edit_cat/' . $cat->id) ?>" class="btn btn-default btn-xs"><?= lang('cat_edit_btn') ?></a>
            <a id="cat-remove-<?= $cat->id ?>" href="<?= site_url('admin_cats/remove_cat/' . $cat->id) ?>" class="btn btn-danger btn-xs"><?= lang('cat_remove_btn') ?></a>
        </td>

        <script>
$('a#cat-remove-<?= $cat->id ?>').confirm({
    title: 'Please Confirm',
    content: "Are you sure you want to remove the category <b><?= $cat->url_name ?></b>?<br><br><b>This action can not be undone!</b>",
    theme: 'supervan'
});

</script>
    </tr>
  <?php endforeach ?>
</table>



