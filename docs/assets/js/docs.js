/* ================================================================
   Pubvana Documentation — docs.js
   Nav render, active state, search, mobile toggle, collapse
   ================================================================ */

const NAV_GROUPS = [
  {
    label: 'Getting Started',
    id: 'getting-started',
    items: [
      { label: 'Welcome',       href: '/docs/index.html' },
      { label: 'Installation',  href: '/docs/getting-started/installation.html' },
      { label: 'First Steps',   href: '/docs/getting-started/first-steps.html' },
    ]
  },
  {
    label: 'Content',
    id: 'content',
    items: [
      { label: 'Posts',              href: '/docs/content/posts.html' },
      { label: 'Pages',              href: '/docs/content/pages.html' },
      { label: 'Categories & Tags',  href: '/docs/content/categories-tags.html' },
      { label: 'Comments',           href: '/docs/content/comments.html' },
      { label: 'Revisions',          href: '/docs/content/revisions.html' },
      { label: 'Media Library',      href: '/docs/content/media.html' },
    ]
  },
  {
    label: 'Design',
    id: 'design',
    items: [
      { label: 'Themes',           href: '/docs/design/themes.html' },
      { label: 'Widgets',          href: '/docs/design/widgets.html' },
      { label: 'Navigation Menus', href: '/docs/design/navigation.html' },
    ]
  },
  {
    label: 'Users & Access',
    id: 'users',
    items: [
      { label: 'Roles & Permissions', href: '/docs/users/roles.html' },
      { label: 'Author Profiles',     href: '/docs/users/profiles.html' },
      { label: 'Social Login',        href: '/docs/users/social-login.html' },
    ]
  },
  {
    label: 'Settings',
    id: 'settings',
    items: [
      { label: 'General',          href: '/docs/settings/general.html' },
      { label: 'Maintenance Mode', href: '/docs/settings/maintenance.html' },
    ]
  },
  {
    label: 'Tools',
    id: 'tools',
    items: [
      { label: 'Marketplace',      href: '/docs/tools/marketplace.html' },
      { label: 'WordPress Import', href: '/docs/tools/wordpress-import.html' },
      { label: 'Redirects',        href: '/docs/tools/redirects.html' },
      { label: 'CLI Commands',     href: '/docs/tools/cli-commands.html' },
    ]
  },
  {
    label: 'Premium Core ⭐',
    id: 'premium',
    premium: true,
    items: [
      { label: 'Overview & Activation', href: '/docs/premium/overview.html',              premium: true },
      { label: 'Analytics',             href: '/docs/premium/analytics.html',             premium: true },
      { label: 'Activity Log',          href: '/docs/premium/activity-log.html',          premium: true },
      { label: 'Post Schedule',         href: '/docs/premium/schedule.html',              premium: true },
      { label: 'Backup & Export',       href: '/docs/premium/backup.html',                premium: true },
      { label: 'Broken Link Checker',   href: '/docs/premium/broken-links.html',          premium: true },
      { label: 'Affiliate Links',       href: '/docs/premium/affiliates.html',            premium: true },
      { label: 'Two-Factor Auth',       href: '/docs/premium/two-factor.html',            premium: true },
      { label: 'SEO',                   href: '/docs/premium/seo.html',                   premium: true },
      { label: 'Social Sharing',        href: '/docs/premium/social-sharing.html',        premium: true },
    ]
  },
];

function renderSidebar() {
  const container = document.getElementById('docs-sidebar-nav');
  if (!container) return;

  const currentPath = window.location.pathname;

  let html = '<div class="sidebar-search"><input type="text" id="sidebar-search-input" placeholder="Search docs\u2026" autocomplete="off"></div>';

  NAV_GROUPS.forEach(group => {
    const collapsed = getGroupState(group.id) === 'collapsed' ? ' collapsed' : '';
    const premiumClass = group.premium ? ' premium-group' : '';
    html += `<div class="nav-group${collapsed}" data-group="${group.id}">`;
    html += `<div class="nav-group-header${premiumClass}"><span>${group.label}</span><span class="chevron">\u25bc</span></div>`;
    html += '<ul class="nav-group-items">';
    group.items.forEach(item => {
      const isActive = isActivePath(currentPath, item.href);
      const activeClass = isActive ? ' class="active"' : '';
      const star = item.premium ? '<span class="nav-star">\u2b50</span>' : '';
      html += `<li><a href="${item.href}"${activeClass}>${star}${item.label}</a></li>`;
    });
    html += '</ul></div>';
  });

  container.innerHTML = html;

  const activeLink = container.querySelector('a.active');
  if (activeLink) {
    setTimeout(() => activeLink.scrollIntoView({ block: 'nearest' }), 100);
  }

  container.querySelectorAll('.nav-group-header').forEach(header => {
    header.addEventListener('click', () => {
      const group = header.closest('.nav-group');
      const id = group.dataset.group;
      group.classList.toggle('collapsed');
      setGroupState(id, group.classList.contains('collapsed') ? 'collapsed' : 'open');
    });
  });

  const searchInput = document.getElementById('sidebar-search-input');
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      const q = searchInput.value.toLowerCase().trim();
      container.querySelectorAll('.nav-group').forEach(group => {
        let anyVisible = false;
        group.querySelectorAll('.nav-group-items li').forEach(li => {
          const text = li.textContent.toLowerCase();
          const match = !q || text.includes(q);
          li.classList.toggle('hidden', !match);
          if (match) anyVisible = true;
        });
        if (q && anyVisible) group.classList.remove('collapsed');
        group.style.display = (q && !anyVisible) ? 'none' : '';
      });
    });
  }
}

function isActivePath(currentPath, href) {
  const normalised = currentPath.replace(/\/$/, '');
  const target = href.replace(/^\/docs/, '').replace(/\/$/, '');
  return normalised.endsWith(target) || normalised === href.replace(/\/$/, '');
}

function getGroupState(id) {
  try { return localStorage.getItem('docs_group_' + id); } catch(e) { return null; }
}

function setGroupState(id, state) {
  try { localStorage.setItem('docs_group_' + id, state); } catch(e) {}
}

function initMobile() {
  const btn = document.getElementById('hamburger');
  if (!btn) return;
  btn.addEventListener('click', () => document.body.classList.toggle('sidebar-open'));
  document.addEventListener('click', e => {
    if (document.body.classList.contains('sidebar-open') &&
        !e.target.closest('#docs-sidebar') &&
        !e.target.closest('#hamburger')) {
      document.body.classList.remove('sidebar-open');
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  renderSidebar();
  initMobile();
});
