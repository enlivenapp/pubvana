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

        <hr class="sidebar-divider">
        <div class="sidebar-heading">Content</div>

        <li class="nav-item <?= ($active_nav ?? '') === 'posts' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('admin/posts') ?>">
                <i class="fas fa-fw fa-edit"></i>
                <span>Posts</span>
            </a>
        </li>

        <li class="nav-item <?= ($active_nav ?? '') === 'pages' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('admin/pages') ?>">
                <i class="fas fa-fw fa-file-alt"></i>
                <span>Pages</span>
            </a>
        </li>

        <li class="nav-item <?= ($active_nav ?? '') === 'categories' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('admin/categories') ?>">
                <i class="fas fa-fw fa-folder"></i>
                <span>Categories</span>
            </a>
        </li>

        <li class="nav-item <?= ($active_nav ?? '') === 'tags' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('admin/tags') ?>">
                <i class="fas fa-fw fa-tags"></i>
                <span>Tags</span>
            </a>
        </li>

        <li class="nav-item <?= ($active_nav ?? '') === 'comments' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('admin/comments') ?>">
                <i class="fas fa-fw fa-comments"></i>
                <span>Comments</span>
            </a>
        </li>

        <li class="nav-item <?= ($active_nav ?? '') === 'media' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('admin/media') ?>">
                <i class="fas fa-fw fa-photo-video"></i>
                <span>Media</span>
            </a>
        </li>

        <hr class="sidebar-divider">
        <div class="sidebar-heading">Appearance</div>

        <?php if (auth()->user()->can('admin.themes')): ?>
        <li class="nav-item <?= ($active_nav ?? '') === 'themes' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('admin/themes') ?>">
                <i class="fas fa-fw fa-palette"></i>
                <span>Themes</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (auth()->user()->can('admin.widgets')): ?>
        <li class="nav-item <?= ($active_nav ?? '') === 'widgets' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('admin/widgets') ?>">
                <i class="fas fa-fw fa-puzzle-piece"></i>
                <span>Widgets</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (auth()->user()->can('admin.navigation')): ?>
        <li class="nav-item <?= ($active_nav ?? '') === 'navigation' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('admin/navigation') ?>">
                <i class="fas fa-fw fa-bars"></i>
                <span>Navigation</span>
            </a>
        </li>
        <?php endif; ?>

        <hr class="sidebar-divider">
        <div class="sidebar-heading">Users &amp; Site</div>

        <?php if (auth()->user()->can('users.manage')): ?>
        <li class="nav-item <?= ($active_nav ?? '') === 'users' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('admin/users') ?>">
                <i class="fas fa-fw fa-users"></i>
                <span>Users</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (auth()->user()->can('admin.settings')): ?>
        <li class="nav-item <?= ($active_nav ?? '') === 'social' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('admin/social') ?>">
                <i class="fas fa-fw fa-share-alt"></i>
                <span>Social Links</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (auth()->user()->can('admin.settings')): ?>
        <li class="nav-item <?= ($active_nav ?? '') === 'redirects' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('admin/redirects') ?>">
                <i class="fas fa-fw fa-exchange-alt"></i>
                <span>Redirects</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (auth()->user()->can('admin.settings')): ?>
        <li class="nav-item <?= ($active_nav ?? '') === 'settings' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('admin/settings') ?>">
                <i class="fas fa-fw fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (auth()->user()->can('admin.marketplace')): ?>
        <hr class="sidebar-divider">
        <div class="sidebar-heading">Marketplace</div>
        <li class="nav-item <?= ($active_nav ?? '') === 'marketplace' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('admin/marketplace') ?>">
                <i class="fas fa-fw fa-store"></i>
                <span>Marketplace</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="https://pubvana.net" target="_blank" rel="noopener">
                <i class="fas fa-fw fa-shopping-cart"></i>
                <span>Pubvana Store</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (!empty($plugin_menu_items ?? [])): ?>
        <hr class="sidebar-divider">
        <div class="sidebar-heading">Plugins</div>
        <?php foreach ($plugin_menu_items as $pluginItem): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?= esc($pluginItem['url']) ?>">
                <i class="fas fa-fw <?= esc($pluginItem['icon'] ?? 'fa-plug') ?>"></i>
                <span><?= esc($pluginItem['label']) ?></span>
            </a>
        </li>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($update['available'] ?? false)): ?>
        <hr class="sidebar-divider">
        <li class="nav-item <?= ($active_nav ?? '') === 'updates' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('admin/updates') ?>">
                <i class="fas fa-fw fa-arrow-circle-up text-warning"></i>
                <span>Update Available
                    <span class="badge badge-warning ml-1"><?= esc($update['latest_version'] ?? '') ?></span>
                </span>
            </a>
        </li>
        <?php endif; ?>

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
