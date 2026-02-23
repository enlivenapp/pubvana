<div class="widget search-form-widget">
    <form action="<?= base_url('search') ?>" method="GET">
        <div class="input-group">
            <input type="text" name="q" class="form-control" placeholder="<?= esc($placeholder ?? 'Search…') ?>">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
        </div>
    </form>
</div>
