<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= esc($page_title ?? 'Admin — Pubvana') ?></title>

    <!-- SB Admin 2 CSS -->
    <link href="<?= base_url('assets/admin/vendor/fontawesome-free/css/all.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/admin/css/sb-admin-2.min.css') ?>" rel="stylesheet">
    <!-- Pubvana Admin overrides -->
    <link href="<?= base_url('assets/cms/admin.css') ?>" rel="stylesheet">

    <?php if (isset($extra_styles)): ?>
        <?= $extra_styles ?>
    <?php endif; ?>
</head>

<body id="page-top">

<!-- Page Wrapper -->
<div id="wrapper">

    <!-- Sidebar -->
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

        <!-- Sidebar Brand -->
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= base_url('admin') ?>">
            <div class="sidebar-brand-icon">
                <i class="fas fa-pen-nib"></i>
            </div>
            <div class="sidebar-brand-text mx-3">Pubvana</div>
        </a>

        <hr class="sidebar-divider my-0">

        <!-- Dashboard -->
        <li class="nav-item <?= ($active_nav ?? '') === 'dashboard' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('admin') ?>">
                <i class="fas fa-fw fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <?php
        // Determine which group is active so the right section stays open
        $nav = $active_nav ?? '';
        $contentOpen     = in_array($nav, ['posts','pages','categories','tags','comments','media','schedule','import'], true);
        $appearanceOpen  = in_array($nav, ['themes','widgets','navigation'], true);
        $siteOpen        = in_array($nav, ['users','social','redirects','settings'], true);
        $toolsOpen       = in_array($nav, ['affiliates','broken_links','analytics','activity_log','backup'], true);
        $marketplaceOpen = in_array($nav, ['marketplace'], true);
        $pluginsOpen     = false; // plugins set their own nav key; default closed
        ?>

        <hr class="sidebar-divider">

        <!-- ===== CONTENT ===== -->
        <li class="nav-item">
            <a class="nav-link <?= $contentOpen ? '' : 'collapsed' ?>"
               href="#collapseContent" data-toggle="collapse" data-target="#collapseContent"
               aria-expanded="<?= $contentOpen ? 'true' : 'false' ?>">
                <i class="fas fa-fw fa-newspaper"></i>
                <span>Content</span>
            </a>
            <div id="collapseContent"
                 class="collapse <?= $contentOpen ? 'show' : '' ?>"
                 data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item <?= $nav === 'posts'      ? 'active' : '' ?>" href="<?= base_url('admin/posts') ?>">Posts</a>
                    <a class="collapse-item <?= $nav === 'schedule'   ? 'active' : '' ?>" href="<?= base_url('admin/schedule') ?>"><i class="fas fa-star fa-xs text-warning mr-1"></i>Schedule</a>
                    <a class="collapse-item <?= $nav === 'pages'      ? 'active' : '' ?>" href="<?= base_url('admin/pages') ?>">Pages</a>
                    <a class="collapse-item <?= $nav === 'categories' ? 'active' : '' ?>" href="<?= base_url('admin/categories') ?>">Categories</a>
                    <a class="collapse-item <?= $nav === 'tags'       ? 'active' : '' ?>" href="<?= base_url('admin/tags') ?>">Tags</a>
                    <a class="collapse-item <?= $nav === 'comments'   ? 'active' : '' ?>" href="<?= base_url('admin/comments') ?>">Comments</a>
                    <a class="collapse-item <?= $nav === 'media'      ? 'active' : '' ?>" href="<?= base_url('admin/media') ?>">Media</a>
                    <?php if (auth()->user()->can('admin.settings')): ?>
                    <a class="collapse-item <?= $nav === 'import'     ? 'active' : '' ?>" href="<?= base_url('admin/import') ?>">Import</a>
                    <?php endif; ?>
                </div>
            </div>
        </li>

        <!-- ===== APPEARANCE ===== -->
        <?php if (auth()->user()->can('admin.themes') || auth()->user()->can('admin.widgets') || auth()->user()->can('admin.navigation')): ?>
        <li class="nav-item">
            <a class="nav-link <?= $appearanceOpen ? '' : 'collapsed' ?>"
               href="#collapseAppearance" data-toggle="collapse" data-target="#collapseAppearance"
               aria-expanded="<?= $appearanceOpen ? 'true' : 'false' ?>">
                <i class="fas fa-fw fa-palette"></i>
                <span>Appearance</span>
            </a>
            <div id="collapseAppearance"
                 class="collapse <?= $appearanceOpen ? 'show' : '' ?>"
                 data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <?php if (auth()->user()->can('admin.themes')): ?>
                    <a class="collapse-item <?= $nav === 'themes'     ? 'active' : '' ?>" href="<?= base_url('admin/themes') ?>">Themes</a>
                    <?php endif; ?>
                    <?php if (auth()->user()->can('admin.widgets')): ?>
                    <a class="collapse-item <?= $nav === 'widgets'    ? 'active' : '' ?>" href="<?= base_url('admin/widgets') ?>">Widgets</a>
                    <?php endif; ?>
                    <?php if (auth()->user()->can('admin.navigation')): ?>
                    <a class="collapse-item <?= $nav === 'navigation' ? 'active' : '' ?>" href="<?= base_url('admin/navigation') ?>">Navigation</a>
                    <?php endif; ?>
                </div>
            </div>
        </li>
        <?php endif; ?>

        <!-- ===== USERS & SITE ===== -->
        <li class="nav-item">
            <a class="nav-link <?= $siteOpen ? '' : 'collapsed' ?>"
               href="#collapseSite" data-toggle="collapse" data-target="#collapseSite"
               aria-expanded="<?= $siteOpen ? 'true' : 'false' ?>">
                <i class="fas fa-fw fa-cog"></i>
                <span>Users &amp; Site</span>
            </a>
            <div id="collapseSite"
                 class="collapse <?= $siteOpen ? 'show' : '' ?>"
                 data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <?php if (auth()->user()->can('users.manage')): ?>
                    <a class="collapse-item <?= $nav === 'users'        ? 'active' : '' ?>" href="<?= base_url('admin/users') ?>">Users</a>
                    <?php endif; ?>
                    <?php if (auth()->user()->can('admin.settings')): ?>
                    <a class="collapse-item <?= $nav === 'social'       ? 'active' : '' ?>" href="<?= base_url('admin/social') ?>">Social Links</a>
                    <a class="collapse-item <?= $nav === 'redirects'    ? 'active' : '' ?>" href="<?= base_url('admin/redirects') ?>">Redirects</a>
                    <a class="collapse-item <?= $nav === 'settings'     ? 'active' : '' ?>" href="<?= base_url('admin/settings') ?>">Settings</a>
                    <?php endif; ?>
                </div>
            </div>
        </li>

        <!-- ===== TOOLS ===== -->
        <?php if (auth()->user()->can('admin.settings')): ?>
        <li class="nav-item">
            <a class="nav-link <?= $toolsOpen ? '' : 'collapsed' ?>"
               href="#collapseTools" data-toggle="collapse" data-target="#collapseTools"
               aria-expanded="<?= $toolsOpen ? 'true' : 'false' ?>">
                <i class="fas fa-fw fa-tools"></i>
                <span>Tools</span>
            </a>
            <div id="collapseTools"
                 class="collapse <?= $toolsOpen ? 'show' : '' ?>"
                 data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item <?= $nav === 'analytics'    ? 'active' : '' ?>" href="<?= base_url('admin/analytics') ?>"><i class="fas fa-star fa-xs text-warning mr-1"></i>Analytics</a>
                    <a class="collapse-item <?= $nav === 'affiliates'   ? 'active' : '' ?>" href="<?= base_url('admin/affiliates') ?>"><i class="fas fa-star fa-xs text-warning mr-1"></i>Affiliate Links</a>
                    <a class="collapse-item <?= $nav === 'broken_links' ? 'active' : '' ?>" href="<?= base_url('admin/broken-links') ?>"><i class="fas fa-star fa-xs text-warning mr-1"></i>Broken Links</a>
                    <a class="collapse-item <?= $nav === 'activity_log' ? 'active' : '' ?>" href="<?= base_url('admin/activity-log') ?>"><i class="fas fa-star fa-xs text-warning mr-1"></i>Activity Log</a>
                    <a class="collapse-item <?= $nav === 'backup'       ? 'active' : '' ?>" href="<?= base_url('admin/backup') ?>"><i class="fas fa-star fa-xs text-warning mr-1"></i>Backup &amp; Export</a>
                </div>
            </div>
        </li>
        <?php endif; ?>

        <!-- ===== MARKETPLACE ===== -->
        <?php if (auth()->user()->can('admin.marketplace')): ?>
        <li class="nav-item">
            <a class="nav-link <?= $marketplaceOpen ? '' : 'collapsed' ?>"
               href="#collapseMarketplace" data-toggle="collapse" data-target="#collapseMarketplace"
               aria-expanded="<?= $marketplaceOpen ? 'true' : 'false' ?>">
                <i class="fas fa-fw fa-store"></i>
                <span>Marketplace</span>
            </a>
            <div id="collapseMarketplace"
                 class="collapse <?= $marketplaceOpen ? 'show' : '' ?>"
                 data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item <?= $nav === 'marketplace' ? 'active' : '' ?>" href="<?= base_url('admin/marketplace') ?>">Browse</a>
                </div>
            </div>
        </li>
        <?php endif; ?>

        <!-- ===== PLUGINS ===== -->
        <?php if (!empty($plugin_menu_items ?? [])): ?>
        <?php $pluginsOpen = in_array($nav, array_column($plugin_menu_items, 'nav_key'), true); ?>
        <li class="nav-item">
            <a class="nav-link <?= $pluginsOpen ? '' : 'collapsed' ?>"
               href="#collapsePlugins" data-toggle="collapse" data-target="#collapsePlugins"
               aria-expanded="<?= $pluginsOpen ? 'true' : 'false' ?>">
                <i class="fas fa-fw fa-plug"></i>
                <span>Plugins</span>
            </a>
            <div id="collapsePlugins"
                 class="collapse <?= $pluginsOpen ? 'show' : '' ?>"
                 data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <?php foreach ($plugin_menu_items as $pluginItem): ?>
                    <a class="collapse-item" href="<?= esc($pluginItem['url']) ?>"><?= esc($pluginItem['label']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </li>
        <?php endif; ?>

        <!-- ===== UPDATE AVAILABLE ===== -->
        <?php if (!empty($update['available'] ?? false)): ?>
        <hr class="sidebar-divider">
        <li class="nav-item <?= $nav === 'updates' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('admin/updates') ?>">
                <i class="fas fa-fw fa-arrow-circle-up text-warning"></i>
                <span>Update Available
                    <span class="badge badge-warning ml-1"><?= esc($update['latest_version'] ?? '') ?></span>
                </span>
            </a>
        </li>
        <?php endif; ?>

        <hr class="sidebar-divider">

        <!-- ===== PINNED BOTTOM LINKS ===== -->
        <?php if (auth()->user()->can('admin.settings')): ?>
        <li class="nav-item <?= $nav === 'premium' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('admin/premium') ?>">
                <i class="fas fa-fw fa-star text-warning"></i>
                <span>Premium</span>
            </a>
        </li>
        <?php endif; ?>

        <li class="nav-item">
            <a class="nav-link" href="https://pubvana.net" target="_blank" rel="noopener">
                <i class="fas fa-fw fa-shopping-cart"></i>
                <span>Pubvana Store</span>
            </a>
        </li>

        <hr class="sidebar-divider d-none d-md-block">

        <!-- Sidebar Toggler -->
        <div class="text-center d-none d-md-inline">
            <button class="rounded-circle border-0" id="sidebarToggle"></button>
        </div>

    </ul>
    <!-- End of Sidebar -->

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <!-- Main Content -->
        <div id="content">

            <!-- Topbar -->
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                <!-- Sidebar Toggle (Topbar) -->
                <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                    <i class="fa fa-bars"></i>
                </button>

                <!-- View Site -->
                <a href="<?= base_url() ?>" target="_blank" class="btn btn-sm btn-outline-secondary ml-2">
                    <i class="fas fa-external-link-alt"></i> View Site
                </a>

                <ul class="navbar-nav ml-auto">
                    <?php if (!empty($update['available'] ?? false)): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('admin/updates') ?>"
                           title="Pubvana <?= esc($update['latest_version'] ?? '') ?> available">
                            <i class="fas fa-arrow-circle-up text-warning"></i>
                            <span class="badge badge-warning badge-counter">!</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <div class="topbar-divider d-none d-sm-block"></div>

                    <!-- User Info -->
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                            <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                <?= esc(auth()->user()->username ?? 'Admin') ?>
                            </span>
                            <i class="fas fa-user-circle fa-fw fa-lg text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                            <a class="dropdown-item" href="<?= base_url() ?>">
                                <i class="fas fa-home fa-sm fa-fw mr-2 text-gray-400"></i> View Site
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout
                            </a>
                        </div>
                    </li>
                </ul>
            </nav>
            <!-- End of Topbar -->

            <!-- Begin Page Content -->
            <div class="container-fluid">

                <!-- Flash Messages -->
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= esc(session()->getFlashdata('success')) ?>
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= esc(session()->getFlashdata('error')) ?>
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('errors')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php foreach ((array) session()->getFlashdata('errors') as $err): ?>
                            <div><?= esc($err) ?></div>
                        <?php endforeach; ?>
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($update['available'] ?? false)): ?>
                    <?= view('admin/partials/update_banner', ['update' => $update]) ?>
                <?php endif; ?>

                <?= $content ?>

            </div>
            <!-- End Page Content -->

        </div>
        <!-- End of Main Content -->

        <!-- Footer -->
        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>Pubvana CMS &copy; <?= date('Y') ?></span>
                </div>
            </div>
        </footer>
    </div>
    <!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->

<!-- Scroll to Top Button -->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ready to Leave?</h5>
                <button class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">Select "Logout" below to end your session.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form action="<?= base_url('logout') ?>" method="POST">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary">Logout</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JS -->
<script src="<?= base_url('assets/admin/vendor/jquery/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/admin/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/admin/vendor/jquery-easing/jquery.easing.min.js') ?>"></script>
<script src="<?= base_url('assets/admin/js/sb-admin-2.min.js') ?>"></script>

<?php if (isset($extra_scripts)): ?>
    <?= $extra_scripts ?>
<?php endif; ?>

</body>
</html>
