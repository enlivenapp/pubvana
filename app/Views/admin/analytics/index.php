<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Analytics</h1>
    <div class="btn-group btn-group-sm" role="group" id="dayFilter">
        <button type="button" class="btn btn-outline-primary <?= $days === 7  ? 'active' : '' ?>" data-days="7">7 days</button>
        <button type="button" class="btn btn-outline-primary <?= $days === 30 ? 'active' : '' ?>" data-days="30">30 days</button>
        <button type="button" class="btn btn-outline-primary <?= $days === 90 ? 'active' : '' ?>" data-days="90">90 days</button>
    </div>
</div>

<!-- Total views stat card -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Page Views (last <?= $days ?> days)
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalViews">
                            <?= number_format($totalViews) ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-eye fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Views per day chart -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Views Per Day</h6>
    </div>
    <div class="card-body">
        <canvas id="viewsChart" height="80"></canvas>
    </div>
</div>

<div class="row">

    <!-- Top posts -->
    <div class="col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Top Posts</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0" id="topPostsTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Post</th>
                            <th style="width:80px" class="text-right">Views</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($topPosts)): ?>
                        <tr><td colspan="2" class="text-center text-muted py-4">No data yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($topPosts as $row): ?>
                        <tr>
                            <td>
                                <a href="<?= esc(post_url($row->slug)) ?>" target="_blank" rel="noopener">
                                    <?= esc($row->title) ?>
                                </a>
                            </td>
                            <td class="text-right font-weight-bold"><?= number_format($row->view_count) ?></td>
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
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Top Referrers</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0" id="referrersTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Domain</th>
                            <th style="width:80px" class="text-right">Views</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($referrers)): ?>
                        <tr><td colspan="2" class="text-center text-muted py-4">No referrer data yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($referrers as $row): ?>
                        <tr>
                            <td class="small"><?= esc($row->referrer_domain) ?></td>
                            <td class="text-right font-weight-bold"><?= number_format($row->view_count) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();

// Pass PHP data to JS before entering NOWDOC
$chartJson    = json_encode($chart);
$dataEndpoint = json_encode(base_url('admin/analytics/data'));
$content .= '<script>window._analyticsChart=' . $chartJson . ';'
          . 'window._analyticsEndpoint=' . $dataEndpoint . ';'
          . 'window._analyticsDays=' . (int) $days . ';</script>';
?>
<?php $extra_scripts = <<<'HTML'
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
var chart = null;

function initChart(data) {
    var ctx = document.getElementById('viewsChart').getContext('2d');
    if (chart) { chart.destroy(); }
    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Views',
                data: data.values,
                fill: true,
                backgroundColor: 'rgba(78,115,223,0.1)',
                borderColor: 'rgba(78,115,223,1)',
                tension: 0.3,
                pointRadius: 3,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
}

function renderTopPosts(rows) {
    var tbody = document.querySelector('#topPostsTable tbody');
    if (!rows || rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted py-4">No data yet.</td></tr>';
        return;
    }
    tbody.innerHTML = rows.map(function(r) {
        return '<tr><td>' + escHtml(r.title) + '</td>'
             + '<td class="text-right font-weight-bold">' + Number(r.view_count).toLocaleString() + '</td></tr>';
    }).join('');
}

function renderReferrers(rows) {
    var tbody = document.querySelector('#referrersTable tbody');
    if (!rows || rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted py-4">No referrer data yet.</td></tr>';
        return;
    }
    tbody.innerHTML = rows.map(function(r) {
        return '<tr><td class="small">' + escHtml(r.referrer_domain) + '</td>'
             + '<td class="text-right font-weight-bold">' + Number(r.view_count).toLocaleString() + '</td></tr>';
    }).join('');
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function fetchData(days) {
    fetch(window._analyticsEndpoint + '?days=' + days)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            document.getElementById('totalViews').textContent = Number(data.totalViews).toLocaleString();
            initChart(data.chart);
            renderTopPosts(data.topPosts);
            renderReferrers(data.referrers);
        });
}

document.addEventListener('DOMContentLoaded', function() {
    initChart(window._analyticsChart);

    document.getElementById('dayFilter').addEventListener('click', function(e) {
        var btn = e.target.closest('[data-days]');
        if (!btn) return;
        document.querySelectorAll('#dayFilter .btn').forEach(function(b) { b.classList.remove('active'); });
        btn.classList.add('active');
        fetchData(btn.dataset.days);
    });
});
</script>
HTML;
?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
