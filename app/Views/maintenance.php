<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance — <?= esc(setting('App.siteName') ?? 'Site') ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8f9fa;
            color: #343a40;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
            padding: 2rem;
        }
        .container { max-width: 480px; }
        h1 { font-size: 2rem; margin-bottom: 1rem; }
        p { font-size: 1.1rem; color: #6c757d; margin-bottom: 1.5rem; }
        .icon { font-size: 4rem; margin-bottom: 1.5rem; }
        .site-name { font-size: 1.25rem; font-weight: 600; color: #495057; margin-bottom: 0.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔧</div>
        <div class="site-name"><?= esc(setting('App.siteName') ?? 'Site') ?></div>
        <h1>Under Maintenance</h1>
        <p>We're performing scheduled maintenance. We'll be back soon — thanks for your patience!</p>
    </div>
</body>
</html>
