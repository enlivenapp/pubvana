<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Post Schedule</h1>
    <a href="<?= base_url('admin/posts/create') ?>" class="btn btn-sm btn-primary">
        <i class="fas fa-plus fa-sm"></i> New Post
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <div id="schedule-calendar"></div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php
// Pass events URL to JS before entering NOWDOC (PHP not evaluated inside <<<'HTML')
$eventsUrl = base_url('admin/schedule/events');
$content .= '<script>window._scheduleEventsUrl=' . json_encode($eventsUrl) . ';</script>';
?>
<?php $extra_scripts = <<<'HTML'
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('schedule-calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 'auto',
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,listMonth'
        },
        events: window._scheduleEventsUrl,
        eventClick: function(info) {
            info.jsEvent.preventDefault();
            if (info.event.url) {
                window.location.href = info.event.url;
            }
        },
        noEventsContent: 'No scheduled posts.',
        eventTimeFormat: {
            hour:   '2-digit',
            minute: '2-digit',
            meridiem: 'short'
        }
    });
    calendar.render();
});
</script>
HTML;
?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
