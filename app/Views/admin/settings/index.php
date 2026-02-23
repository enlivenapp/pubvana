<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Settings</h1>
</div>

<ul class="nav nav-tabs mb-3" id="settingsTabs" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#general">General</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#seo">SEO</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#email">Email</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#social">Social Login</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#sharing">Social Sharing</a></li>
</ul>

<div class="tab-content">

    <!-- General -->
    <div class="tab-pane fade show active" id="general">
        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">General Settings</h6></div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('admin/settings/general') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Site Name</label>
                        <div class="col-sm-9"><input type="text" name="site_name" class="form-control" value="<?= esc(setting('App.siteName')) ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Tagline</label>
                        <div class="col-sm-9"><input type="text" name="site_tagline" class="form-control" value="<?= esc(setting('App.siteTagline')) ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Admin Email</label>
                        <div class="col-sm-9"><input type="email" name="site_email" class="form-control" value="<?= esc(setting('App.siteEmail')) ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Posts Per Page</label>
                        <div class="col-sm-3"><input type="number" name="posts_per_page" class="form-control" min="1" max="100" value="<?= esc(setting('App.postsPerPage') ?? 10) ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Comments</label>
                        <div class="col-sm-9">
                            <div class="custom-control custom-switch mb-2">
                                <input type="hidden" name="comments_enabled" value="0">
                                <input type="checkbox" class="custom-control-input" id="comments_enabled" name="comments_enabled" value="1"
                                       <?= setting('App.commentsEnabled') ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="comments_enabled">Enable comments</label>
                            </div>
                            <div class="custom-control custom-switch">
                                <input type="hidden" name="comment_moderation" value="0">
                                <input type="checkbox" class="custom-control-input" id="comment_moderation" name="comment_moderation" value="1"
                                       <?= setting('App.commentModeration') ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="comment_moderation">Require moderation before publishing</label>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Front Page</label>
                        <div class="col-sm-9">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="front_page_type" id="fp_blog" value="blog"
                                       <?= setting('App.frontPageType') !== 'page' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="fp_blog">Blog index (latest posts)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="front_page_type" id="fp_page" value="page"
                                       <?= setting('App.frontPageType') === 'page' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="fp_page">Static page:</label>
                            </div>
                            <div class="mt-2 ml-4">
                                <select name="front_page_id" class="form-control" id="front_page_id" style="max-width:300px">
                                    <option value="">— Select a page —</option>
                                    <?php foreach ($pages as $p): ?>
                                    <option value="<?= $p->id ?>" <?= setting('App.frontPageId') == $p->id ? 'selected' : '' ?>>
                                        <?= esc($p->title) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">Save General Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SEO -->
    <div class="tab-pane fade" id="seo">
        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">SEO Settings</h6></div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('admin/settings/seo') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Meta Description</label>
                        <div class="col-sm-9">
                            <textarea name="meta_description" class="form-control" rows="3"><?= esc(setting('Seo.metaDescription')) ?></textarea>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Google Analytics ID</label>
                        <div class="col-sm-9"><input type="text" name="google_analytics" class="form-control" placeholder="G-XXXXXXXXXX" value="<?= esc(setting('Seo.googleAnalytics')) ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Sitemap</label>
                        <div class="col-sm-9">
                            <div class="custom-control custom-switch">
                                <input type="hidden" name="sitemap_enabled" value="0">
                                <input type="checkbox" class="custom-control-input" id="sitemap_enabled" name="sitemap_enabled" value="1"
                                       <?= setting('Seo.sitemapEnabled') ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="sitemap_enabled">Enable sitemap.xml</label>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">Save SEO Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Email -->
    <div class="tab-pane fade" id="email">
        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Email Settings</h6></div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('admin/settings/email') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">From Name</label>
                        <div class="col-sm-9"><input type="text" name="email_from_name" class="form-control" value="<?= esc(setting('Email.fromName')) ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">From Address</label>
                        <div class="col-sm-9"><input type="email" name="email_from_address" class="form-control" value="<?= esc(setting('Email.fromAddress')) ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Protocol</label>
                        <div class="col-sm-4">
                            <select name="email_protocol" class="form-control">
                                <?php foreach (['mail' => 'PHP Mail', 'smtp' => 'SMTP', 'sendmail' => 'Sendmail'] as $val => $lbl): ?>
                                <option value="<?= $val ?>" <?= setting('Email.protocol') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div id="smtp-fields">
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label font-weight-bold">SMTP Host</label>
                            <div class="col-sm-9"><input type="text" name="smtp_host" class="form-control" value="<?= esc(setting('Email.SMTPHost')) ?>"></div>
                        </div>
                        <div class="form-row">
                            <div class="col-md-3 offset-md-3 form-group">
                                <label>SMTP Port</label>
                                <input type="number" name="smtp_port" class="form-control" value="<?= esc(setting('Email.SMTPPort') ?? 587) ?>">
                            </div>
                            <div class="col-md-3 form-group">
                                <label>Encryption</label>
                                <select name="smtp_crypto" class="form-control">
                                    <option value="">None</option>
                                    <option value="tls" <?= setting('Email.SMTPCrypto') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                    <option value="ssl" <?= setting('Email.SMTPCrypto') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label font-weight-bold">SMTP Username</label>
                            <div class="col-sm-9"><input type="text" name="smtp_user" class="form-control" value="<?= esc(setting('Email.SMTPUser')) ?>"></div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label font-weight-bold">SMTP Password</label>
                            <div class="col-sm-9"><input type="password" name="smtp_pass" class="form-control" autocomplete="new-password"></div>
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">Save Email Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Social Login -->
    <div class="tab-pane fade" id="social">
        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Social Login (OAuth)</h6></div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Credentials are saved to your <code>.env</code> file.
                    Register your app at
                    <a href="https://console.developers.google.com" target="_blank">Google</a>
                    and <a href="https://developers.facebook.com" target="_blank">Facebook</a>
                    to obtain client IDs and secrets.
                </p>
                <form method="POST" action="<?= base_url('admin/settings/social') ?>">
                    <?= csrf_field() ?>

                    <h6 class="font-weight-bold"><i class="fab fa-google text-danger"></i> Google</h6>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Client ID</label>
                        <div class="col-sm-9"><input type="text" name="google_client_id" class="form-control"
                            value="<?= esc(env('oauth.google.clientId')) ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Client Secret</label>
                        <div class="col-sm-9"><input type="password" name="google_client_secret" class="form-control"
                            autocomplete="new-password" placeholder="(leave blank to keep existing)"></div>
                    </div>

                    <hr>
                    <h6 class="font-weight-bold"><i class="fab fa-facebook text-primary"></i> Facebook</h6>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">App ID</label>
                        <div class="col-sm-9"><input type="text" name="facebook_client_id" class="form-control"
                            value="<?= esc(env('oauth.facebook.clientId')) ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">App Secret</label>
                        <div class="col-sm-9"><input type="password" name="facebook_client_secret" class="form-control"
                            autocomplete="new-password" placeholder="(leave blank to keep existing)"></div>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">Save Social Login Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Social Sharing -->
    <div class="tab-pane fade" id="sharing">
        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Social Auto-Share on Publish</h6></div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    When a post is published with "Share on publish" checked, Pubvana will automatically
                    post to configured social accounts.
                </p>
                <form method="POST" action="<?= base_url('admin/settings/sharing') ?>">
                    <?= csrf_field() ?>

                    <h6 class="font-weight-bold"><i class="fab fa-twitter text-info"></i> Twitter / X</h6>
                    <p class="text-muted small">Get keys at <a href="https://developer.twitter.com" target="_blank">developer.twitter.com</a> → Your App → Keys and Tokens.</p>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">API Key</label>
                        <div class="col-sm-9"><input type="password" name="twitter_api_key" class="form-control"
                            autocomplete="new-password" placeholder="(leave blank to keep existing)"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">API Secret</label>
                        <div class="col-sm-9"><input type="password" name="twitter_api_secret" class="form-control"
                            autocomplete="new-password" placeholder="(leave blank to keep existing)"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Access Token</label>
                        <div class="col-sm-9"><input type="password" name="twitter_access_token" class="form-control"
                            autocomplete="new-password" placeholder="(leave blank to keep existing)"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Access Secret</label>
                        <div class="col-sm-9"><input type="password" name="twitter_access_secret" class="form-control"
                            autocomplete="new-password" placeholder="(leave blank to keep existing)"></div>
                    </div>

                    <hr>
                    <h6 class="font-weight-bold"><i class="fab fa-facebook text-primary"></i> Facebook Page</h6>
                    <p class="text-muted small">Requires a Page Access Token with <code>pages_manage_posts</code> permission.</p>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Page ID</label>
                        <div class="col-sm-9"><input type="text" name="fb_page_id" class="form-control"
                            value="<?= esc(env('sharing.facebook.pageId')) ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Page Access Token</label>
                        <div class="col-sm-9"><input type="password" name="fb_page_token" class="form-control"
                            autocomplete="new-password" placeholder="(leave blank to keep existing)"></div>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">Save Sharing Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?php $content = ob_get_clean(); ?>
<?php $extra_scripts = <<<'SCRIPT'
<script>
function toggleSmtp() {
    var proto = document.querySelector('[name="email_protocol"]').value;
    document.getElementById('smtp-fields').style.display = proto === 'smtp' ? '' : 'none';
}
document.querySelector('[name="email_protocol"]').addEventListener('change', toggleSmtp);
toggleSmtp();
</script>
SCRIPT;
?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
