<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activate your account</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/theme/default/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/theme/default/css/pubvana.css">
</head>
<body>
    <div class="auth-shell">
        <div class="auth-card">
            <h1 class="auth-title">Activate your account</h1>
            <p class="auth-sub">One step left</p>
{% if error %}
    <div class="auth-alert">{{ error }}</div>
{% endif %}

<div class="auth-alert auth-alert-ok">
    We've sent an activation link to your email address. Check your inbox and
    click the link to activate your account.
</div>

<div class="auth-links">
    <a href="/auth/login">Back to sign in</a>
</div>
        </div>
    </div>
</body>
</html>
