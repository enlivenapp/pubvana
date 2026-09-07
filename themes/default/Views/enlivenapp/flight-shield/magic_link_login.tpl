<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magic Link Sign-In</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/theme/default/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/theme/default/css/pubvana.css">
</head>
<body>
    <div class="auth-shell">
        <div class="auth-card">
            <h1 class="auth-title">Magic Link Sign-In</h1>
            <p class="auth-sub">We will email you a one-time sign-in link</p>
{% if error %}
    <div class="auth-alert">{{ error }}</div>
{% endif %}

<form method="post" action="/auth/magic-link" autocomplete="off">
    {% csrf_field %}

    <div class="auth-field">
        <label class="auth-label" for="email">Email</label>
        <input type="email" id="email" name="email" class="auth-input" required autofocus>
    </div>

    <button type="submit" class="btn btn-primary auth-submit">Email Me a Link</button>
</form>

<div class="auth-links">
    <a href="/auth/login">Back to sign in</a>
</div>
        </div>
    </div>
</body>
</html>
