<?php
/**
 * Pubvana Admin Layout
 *
 * Two-tier topbar: brand bar + nav bar, then content area.
 * Tabler (Bootstrap 5) + Alpine.js + HTMX + Jodit
 *
 * Security model:
 *   - Core menu items (Settings: General, Users, Groups, Permissions) are hardcoded
 *   - Plugins can ADD items to any slot via adext, but cannot:
 *     - Overwrite core items
 *     - Overwrite another plugin's items
 *     - Remove any items
 *   - First registration wins on conflict, rejection is logged
 *
 * @var string $content    Page content (rendered by AdminController::render)
 * @var string $pageTitle  Page title
 * @var string $siteName   Site name from config
 * @var object|null $user  Authenticated user entity
 * @var string $userGroups Comma-separated group names
 * @var array $menuSlots   Plugin-registered menu items by slot
 */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> - <?= htmlspecialchars($siteName ?? 'Pubvana') ?></title>
    <link rel="stylesheet" href="/admin-assets/dist/css/tabler.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css"/>
    <link rel="stylesheet" href="/admin-assets/css/admin.css"/>
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jodit@4/es2021/jodit.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jodit@4/es2021/jodit.min.js"></script>
</head>
<body
    class="layout-fluid"
    x-data="{ darkMode: localStorage.getItem('pv-dark-mode') === '1' }"
    x-init="$watch('darkMode', val => { localStorage.setItem('pv-dark-mode', val ? '1' : '0'); val ? document.body.setAttribute('data-bs-theme','dark') : document.body.removeAttribute('data-bs-theme') }); if(darkMode) document.body.setAttribute('data-bs-theme','dark')"
>
<div class="page">

    <!-- ===== Top bar: branding + user ===== -->
    <header class="navbar navbar-expand-md d-print-none">
        <div class="container-xl">
            <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                <a href="/" target="_blank" rel="noopener" class="me-2">
                    <img src="/admin-assets/img/pubvana-logo.png" alt="Pubvana" width="28" height="28">
                </a>
                <a href="/admin">
                    <span class="nav-link-title"><?= htmlspecialchars($siteName ?? 'Pubvana') ?></span>
                </a>
            </h1>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu"
                    aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="navbar-nav flex-row order-md-last">
                <div class="nav-item d-flex align-items-center">
                    <a class="nav-link px-2" href="#" @click.prevent="darkMode = !darkMode" title="Toggle dark mode">
                        <i class="ti" :class="darkMode ? 'ti-sun' : 'ti-moon'" style="font-size:1.25rem"></i>
                    </a>
                </div>

                <div class="nav-item dropdown">
                    <a href="#" class="nav-link d-flex lh-1 text-reset p-0 ps-2" data-bs-toggle="dropdown" aria-label="User menu">
                        <?php $displayName = $user->username ?? 'Admin'; ?>
                        <span class="avatar avatar-sm rounded-circle bg-blue-lt">
                            <?= strtoupper(substr($displayName, 0, 1)) ?>
                        </span>
                        <div class="d-none d-xl-block ps-2">
                            <div><?= htmlspecialchars($displayName) ?></div>
                            <div class="mt-1 small text-secondary"><?= htmlspecialchars($userGroups) ?></div>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                        <a href="/admin/profile" class="dropdown-item">
                            <i class="ti ti-user me-2"></i>Profile
                        </a>
                        <div class="dropdown-divider"></div>
                        <form method="post" action="/auth/logout">
                            <?= csrf_field() ?>
                            <button type="submit" class="dropdown-item">
                                <i class="ti ti-logout me-2"></i>Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- ===== Nav bar: categories ===== -->
    <header class="navbar-expand-md">
        <div class="collapse navbar-collapse" id="navbar-menu">
            <div class="navbar">
                <div class="container-xl">
                    <ul class="navbar-nav">

                        <!-- Dashboard (no dropdown) -->
                        <li class="nav-item">
                            <a class="nav-link" href="/admin">
                                <span class="nav-link-icon"><i class="ti ti-dashboard"></i></span>
                                <span class="nav-link-title">Dashboard</span>
                            </a>
                        </li>

                        <!-- Content -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"
                               data-bs-auto-close="outside" role="button" aria-expanded="false">
                                <span class="nav-link-icon"><i class="ti ti-file-text"></i></span>
                                <span class="nav-link-title">Content</span>
                            </a>
                            <div class="dropdown-menu">
                                <?php if (!empty($menuSlots['content'])): ?>
                                    <?php foreach ($menuSlots['content'] as $item): ?>
                                        <?php if (!empty($item['submenu'])): ?>
                                            <div class="dropend">
                                                <a class="dropdown-item dropdown-toggle" href="#" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                                                    <i class="ti <?= htmlspecialchars($item['icon'] ?? 'ti-point') ?> me-2"></i><?= htmlspecialchars($item['label']) ?>
                                                </a>
                                                <div class="dropdown-menu">
                                                    <?php foreach ($item['submenu'] as $sub): ?>
                                                        <a class="dropdown-item" href="<?= htmlspecialchars($sub['url']) ?>">
                                                            <i class="ti <?= htmlspecialchars($sub['icon'] ?? 'ti-point') ?> me-2"></i><?= htmlspecialchars($sub['label']) ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <a class="dropdown-item" href="<?= htmlspecialchars($item['url']) ?>">
                                                <i class="ti <?= htmlspecialchars($item['icon'] ?? 'ti-point') ?> me-2"></i><?= htmlspecialchars($item['label']) ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="dropdown-header">No content modules installed</span>
                                <?php endif; ?>
                            </div>
                        </li>

                        <!-- Appearance -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"
                               data-bs-auto-close="outside" role="button" aria-expanded="false">
                                <span class="nav-link-icon"><i class="ti ti-palette"></i></span>
                                <span class="nav-link-title">Appearance</span>
                            </a>
                            <div class="dropdown-menu">
                                <?php if (!empty($menuSlots['appearance'])): ?>
                                    <?php foreach ($menuSlots['appearance'] as $item): ?>
                                        <?php if (!empty($item['submenu'])): ?>
                                            <div class="dropend">
                                                <a class="dropdown-item dropdown-toggle" href="#" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                                                    <i class="ti <?= htmlspecialchars($item['icon'] ?? 'ti-point') ?> me-2"></i><?= htmlspecialchars($item['label']) ?>
                                                </a>
                                                <div class="dropdown-menu">
                                                    <?php foreach ($item['submenu'] as $sub): ?>
                                                        <a class="dropdown-item" href="<?= htmlspecialchars($sub['url']) ?>">
                                                            <i class="ti <?= htmlspecialchars($sub['icon'] ?? 'ti-point') ?> me-2"></i><?= htmlspecialchars($sub['label']) ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <a class="dropdown-item" href="<?= htmlspecialchars($item['url']) ?>">
                                                <i class="ti <?= htmlspecialchars($item['icon'] ?? 'ti-point') ?> me-2"></i><?= htmlspecialchars($item['label']) ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="dropdown-header">No appearance modules installed</span>
                                <?php endif; ?>
                            </div>
                        </li>

                        <!-- Plugins -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"
                               data-bs-auto-close="outside" role="button" aria-expanded="false">
                                <span class="nav-link-icon"><i class="ti ti-plug"></i></span>
                                <span class="nav-link-title">Plugins</span>
                            </a>
                            <div class="dropdown-menu">
                                <?php if (!empty($menuSlots['plugins'])): ?>
                                    <?php foreach ($menuSlots['plugins'] as $item): ?>
                                        <?php if (!empty($item['submenu'])): ?>
                                            <div class="dropend">
                                                <a class="dropdown-item dropdown-toggle" href="#" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                                                    <i class="ti <?= htmlspecialchars($item['icon'] ?? 'ti-point') ?> me-2"></i><?= htmlspecialchars($item['label']) ?>
                                                </a>
                                                <div class="dropdown-menu">
                                                    <?php foreach ($item['submenu'] as $sub): ?>
                                                        <a class="dropdown-item" href="<?= htmlspecialchars($sub['url']) ?>">
                                                            <i class="ti <?= htmlspecialchars($sub['icon'] ?? 'ti-point') ?> me-2"></i><?= htmlspecialchars($sub['label']) ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <a class="dropdown-item" href="<?= htmlspecialchars($item['url']) ?>">
                                                <i class="ti <?= htmlspecialchars($item['icon'] ?? 'ti-point') ?> me-2"></i><?= htmlspecialchars($item['label']) ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="dropdown-header">No plugins installed</span>
                                <?php endif; ?>
                            </div>
                        </li>

                        <!-- Tools -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"
                               data-bs-auto-close="outside" role="button" aria-expanded="false">
                                <span class="nav-link-icon"><i class="ti ti-tool"></i></span>
                                <span class="nav-link-title">Tools</span>
                            </a>
                            <div class="dropdown-menu">
                                <?php if (!empty($menuSlots['tools'])): ?>
                                    <?php foreach ($menuSlots['tools'] as $item): ?>
                                        <?php if (!empty($item['submenu'])): ?>
                                            <div class="dropend">
                                                <a class="dropdown-item dropdown-toggle" href="#" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                                                    <i class="ti <?= htmlspecialchars($item['icon'] ?? 'ti-point') ?> me-2"></i><?= htmlspecialchars($item['label']) ?>
                                                </a>
                                                <div class="dropdown-menu">
                                                    <?php foreach ($item['submenu'] as $sub): ?>
                                                        <a class="dropdown-item" href="<?= htmlspecialchars($sub['url']) ?>">
                                                            <i class="ti <?= htmlspecialchars($sub['icon'] ?? 'ti-point') ?> me-2"></i><?= htmlspecialchars($sub['label']) ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <a class="dropdown-item" href="<?= htmlspecialchars($item['url']) ?>">
                                                <i class="ti <?= htmlspecialchars($item['icon'] ?? 'ti-point') ?> me-2"></i><?= htmlspecialchars($item['label']) ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="dropdown-header">No tool modules installed</span>
                                <?php endif; ?>
                            </div>
                        </li>

                        <!-- Settings -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"
                               data-bs-auto-close="outside" role="button" aria-expanded="false">
                                <span class="nav-link-icon"><i class="ti ti-settings"></i></span>
                                <span class="nav-link-title">Settings</span>
                            </a>
                            <div class="dropdown-menu">
                                <!-- Plugin items: appended after core -->
                                <?php if (!empty($menuSlots['settings'])): ?>
                                    <?php foreach ($menuSlots['settings'] as $item): ?>
                                        <?php if (!empty($item['submenu'])): ?>
                                            <div class="dropend">
                                                <a class="dropdown-item dropdown-toggle" href="#" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                                                    <i class="ti <?= htmlspecialchars($item['icon'] ?? 'ti-point') ?> me-2"></i><?= htmlspecialchars($item['label']) ?>
                                                </a>
                                                <div class="dropdown-menu">
                                                    <?php foreach ($item['submenu'] as $sub): ?>
                                                        <a class="dropdown-item" href="<?= htmlspecialchars($sub['url']) ?>">
                                                            <i class="ti <?= htmlspecialchars($sub['icon'] ?? 'ti-point') ?> me-2"></i><?= htmlspecialchars($sub['label']) ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <a class="dropdown-item" href="<?= htmlspecialchars($item['url']) ?>">
                                                <i class="ti <?= htmlspecialchars($item['icon'] ?? 'ti-point') ?> me-2"></i><?= htmlspecialchars($item['label']) ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </li>

                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- ===== Page content ===== -->
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="page-pretitle">Pubvana CMS</div>
                <h2 class="page-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h2>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <?php $session = \Flight::app()->session(); ?>
                <?php foreach (['success', 'info', 'warning', 'danger', 'error'] as $type): ?>
                    <?php if ($session->hasFlash($type)): ?>
                        <?php $cssClass = $type === 'error' ? 'danger' : $type; ?>
                        <div class="alert alert-<?= $cssClass ?> alert-dismissible" role="alert">
                            <div class="d-flex">
                                <div>
                                    <?php if ($cssClass === 'success'): ?>
                                        <i class="ti ti-circle-check icon alert-icon"></i>
                                    <?php elseif ($cssClass === 'danger'): ?>
                                        <i class="ti ti-alert-circle icon alert-icon"></i>
                                    <?php elseif ($cssClass === 'warning'): ?>
                                        <i class="ti ti-alert-triangle icon alert-icon"></i>
                                    <?php else: ?>
                                        <i class="ti ti-info-circle icon alert-icon"></i>
                                    <?php endif; ?>
                                </div>
                                <div><?= htmlspecialchars($session->pullFlash($type)) ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?= $content ?>
            </div>
        </div>
    </div>

</div>

<script src="/admin-assets/dist/js/tabler.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/htmx.org@latest/dist/htmx.min.js"></script>
</body>
</html>
