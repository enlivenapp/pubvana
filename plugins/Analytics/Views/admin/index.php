<?php
/**
 * Analytics report.
 *
 * @var string $pageTitle
 * @var string $range
 * @var array  $report  See AnalyticsService::dashboard()
 * @var bool   $trackingEnabled
 * @var string $adminBase
 */
?>
<?php
$totalViews = (int) ($report['totalViews'] ?? 0);
$topContent = $report['topContent'] ?? [];
$referrers  = $report['referrers'] ?? [];
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <h2 class="page-title mb-0">
        <i class="ti ti-report-analytics me-2"></i>
        Analytics
    </h2>
    <div class="d-flex align-items-center gap-3">
        <div class="btn-group btn-group-sm" role="group" aria-label="Range filter" id="rangeFilter">
            <button type="button" class="btn btn-outline-primary <?= $range === '7' ? 'active' : '' ?>" data-range="7">7d</button>
            <button type="button" class="btn btn-outline-primary <?= $range === '30' ? 'active' : '' ?>" data-range="30">30d</button>
            <button type="button" class="btn btn-outline-primary <?= $range === '90' ? 'active' : '' ?>" data-range="90">90d</button>
            <button type="button" class="btn btn-outline-primary <?= $range === '180' ? 'active' : '' ?>" data-range="180">180d</button>
            <button type="button" class="btn btn-outline-primary <?= $range === '365' ? 'active' : '' ?>" data-range="365">1y</button>
            <button type="button" class="btn btn-outline-primary <?= $range === 'all' ? 'active' : '' ?>" data-range="all">All</button>
        </div>
        <form method="post" action="<?= $adminBase ?>/tracking" class="mb-0">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="tracking_enabled" id="trackingToggleField" value="0">
            <label class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="trackingToggle"
                       <?= $trackingEnabled ? 'checked' : '' ?>
                       onchange="document.getElementById('trackingToggleField').value = this.checked ? '1' : '0'; this.form.submit();">
                <span class="form-check-label small">Track page views</span>
            </label>
        </form>
    </div>
</div>

<?php if (!$trackingEnabled): ?>
<div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
    <i class="ti ti-alert-triangle me-2"></i>
    <div>
        Tracking is currently <strong>disabled</strong>, so no new views are recorded. Flip the switch above to start tracking.
    </div>
</div>
<?php endif; ?>

<!-- Total views stat card -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Total Views</div>
                <div class="h1 mb-0" id="totalViews"><?= number_format($totalViews) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Views chart -->
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Views over time</h3>
    </div>
    <div class="card-body">
        <div id="chartEmpty" class="text-center text-secondary py-4" style="display:none;">
            No views recorded in this period yet.
        </div>
        <div style="position: relative; height: 320px;">
            <canvas id="viewsChart"></canvas>
        </div>
    </div>
</div>

<div class="row">

    <!-- Top content -->
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Top Content</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table" id="topContentTable">
                    <thead>
                        <tr>
                            <th class="w-1">#</th>
                            <th>Path</th>
                            <th>Group</th>
                            <th class="w-1 text-end">Views</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($topContent)): ?>
                        <tr><td colspan="4" class="text-center text-secondary py-4">No views recorded in this period yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($topContent as $index => $row): ?>
                        <tr>
                            <td class="text-secondary"><?= (int) $index + 1 ?></td>
                            <td>
                                <a href="<?= htmlspecialchars($row['page_path']) ?>" target="_blank" rel="noopener">
                                    <?= htmlspecialchars($row['page_path']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-secondary-lt"><?= htmlspecialchars($row['page_group']) ?></span>
                            </td>
                            <td class="text-end fw-bold"><?= number_format((int) $row['view_count']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top referrers -->
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Top Referrers</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table" id="referrersTable">
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th class="w-1 text-end">Views</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($referrers)): ?>
                        <tr><td colspan="2" class="text-center text-secondary py-4">No referrer data in this period.</td></tr>
                    <?php else: ?>
                        <?php foreach ($referrers as $row): ?>
                        <tr>
                            <td class="small"><?= htmlspecialchars($row['referrer_domain']) ?></td>
                            <td class="text-end fw-bold"><?= number_format((int) $row['view_count']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
window._analyticsEndpoint = '<?= $adminBase ?>/data';
window._analyticsRange = <?= json_encode($range) ?>;
window._analyticsReport = <?= json_encode($report) ?>;

var chart = null;

var palette = [
    'rgba(32, 107, 196, 1)',
    'rgba(47, 179, 146, 1)',
    'rgba(245, 158, 11, 1)',
    'rgba(214, 66, 66, 1)',
    'rgba(124, 58, 237, 1)',
    'rgba(14, 165, 233, 1)',
    'rgba(236, 72, 153, 1)',
    'rgba(22, 163, 74, 1)',
    'rgba(202, 138, 4, 1)',
    'rgba(100, 116, 139, 1)'
];

function fillColor(hex) {
    return hex.replace(', 1)', ', 0.12)');
}

function initChart(data) {
    var ctx = document.getElementById('viewsChart');
    if (!ctx) return;
    if (chart) { chart.destroy(); }

    var emptyBox = document.getElementById('chartEmpty');
    var series = data.series || [];
    var hasData = series.length > 0;

    emptyBox.style.display = hasData ? 'none' : 'block';

    var datasets = series.map(function(entry, i) {
        var color = palette[i % palette.length];
        return {
            label: entry.label,
            data: entry.values,
            fill: true,
            backgroundColor: fillColor(color),
            borderColor: color,
            tension: 0.3,
            pointRadius: 2,
            pointHoverRadius: 4,
            borderWidth: 2
        };
    });

    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels || [],
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: true, position: 'bottom' }
            },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
}

function renderTopContent(rows) {
    var tbody = document.querySelector('#topContentTable tbody');
    if (!tbody) return;

    if (!rows || rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-secondary py-4">No views recorded in this period yet.</td></tr>';
        return;
    }

    tbody.innerHTML = rows.map(function(row, i) {
        return '<tr>'
            + '<td class="text-secondary">' + (i + 1) + '</td>'
            + '<td><a href="' + escAttr(row.page_path) + '" target="_blank" rel="noopener">' + escHtml(row.page_path) + '</a></td>'
            + '<td><span class="badge bg-secondary-lt">' + escHtml(row.page_group) + '</span></td>'
            + '<td class="text-end fw-bold">' + Number(row.view_count).toLocaleString() + '</td>'
            + '</tr>';
    }).join('');
}

function renderReferrers(rows) {
    var tbody = document.querySelector('#referrersTable tbody');
    if (!tbody) return;

    if (!rows || rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="2" class="text-center text-secondary py-4">No referrer data in this period.</td></tr>';
        return;
    }

    tbody.innerHTML = rows.map(function(row) {
        return '<tr>'
            + '<td class="small">' + escHtml(row.referrer_domain) + '</td>'
            + '<td class="text-end fw-bold">' + Number(row.view_count).toLocaleString() + '</td>'
            + '</tr>';
    }).join('');
}

function fetchData(range) {
    fetch(window._analyticsEndpoint + '?range=' + encodeURIComponent(range))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            document.getElementById('totalViews').textContent = Number(data.totalViews || 0).toLocaleString();
            initChart(data.trends || { labels: [], series: [] });
            renderTopContent(data.topContent || []);
            renderReferrers(data.referrers || []);
        })
        .catch(function() {});
}

function escHtml(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function escAttr(str) {
    return escHtml(str);
}

document.addEventListener('DOMContentLoaded', function() {
    initChart(window._analyticsReport.trends || { labels: [], series: [] });

    document.getElementById('rangeFilter').addEventListener('click', function(e) {
        var btn = e.target.closest('[data-range]');
        if (!btn) return;
        document.querySelectorAll('#rangeFilter .btn').forEach(function(b) { b.classList.remove('active'); });
        btn.classList.add('active');
        fetchData(btn.dataset.range);
    });
});
</script>