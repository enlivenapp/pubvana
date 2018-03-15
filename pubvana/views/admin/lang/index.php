<p><?= lang('languages_hdr_help_txt') ?></p>

<table class="table table-condensed table-hover">
    <tr>
        <th><?= lang('languages_table_lang_h') ?></th>
        <th><?= lang('languages_table_abbr_h') ?></th>
        <th><?= lang('languages_table_is_default_h') ?></th>
        <th><?= lang('languages_table_enabled_h') ?></th>
        <th></th>
    </tr>
    <?php foreach ($langs as $item): ?>
        <tr>
        <td><?= ucfirst($item['language']) ?></td>
        <td><?= $item['abbreviation'] ?></td>
        <td><?php echo ($item['is_default'] == '1') ? lang('yes') : lang('no'); ?></td>
        <td><?php echo ($item['is_avail'] == '1') ? lang('yes') : lang('no'); ?></td>
        <td class="text-right">

            <?php if ($item['is_default'] == '0'): ?>
                <a id="default-lang-<?= $item['id']; ?>" href="<?= site_url('admin_lang/make_default/' . $item['id']) ?>" class="btn btn-default btn-xs"><?= lang('languages_make_default_btn') ?></a>
                <script>
                $('a#default-lang-<?= $item['id'] ?>').confirm({
                    title: 'Please Confirm',
                    content: "Are you sure you want to make <b><?= ucfirst($item['language']) ?></b> the default language?",
                    theme: 'supervan'
                });

            </script>

            <?php endif ?>
            <?php if ($item['is_avail'] == '1'): ?>
                <?php if ($item['is_default'] == '0'): ?>
                <a id="disable-lang-<?= $item['id']; ?>" href="<?= site_url('admin_lang/disable/' . $item['id']); ?>" class="btn btn-default btn-xs"><?= lang('languages_disable_btn') ?></a>
                <?php endif ?>
            <?php else: ?>
                <a href="<?= site_url('admin_lang/enable/' . $item['id']) ?>" class="btn btn-default btn-xs"><?= lang('languages_enable_btn') ?></a>
            <?php endif ?>
            <script>
                $('a#disable-lang-<?= $item['id'] ?>').confirm({
                    title: 'Please Confirm',
                    content: "Are you sure you want to disable the <b><?= ucfirst($item['language']) ?></b> language?<br><br><b>This action can not be undone!</b>",
                    theme: 'supervan'
                });

            </script>
            
            
            
        </td>
    </tr>
  <?php endforeach ?>
</table>
<p><?= lang('languages_help_text') ?></p>






