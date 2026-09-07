<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/theme/default/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/theme/default/css/pubvana.css">
</head>
<body>
    <div class="auth-shell">
        <div class="auth-card">
            <h1 class="auth-title">Sign in</h1>
            <p class="auth-sub">Welcome back</p>
{% if error %}
    <div class="auth-alert">{{ error }}</div>
{% endif %}

<form method="post" action="/auth/login" autocomplete="off">
    {% csrf_field %}

    <div class="auth-field">
        <label class="auth-label" for="email">Email</label>
        <input type="email" id="email" name="email" class="auth-input" required autofocus>
    </div>

    <div class="auth-field">
        <label class="auth-label" for="password">Password</label>
        <input type="password" id="password" name="password" class="auth-input" required>
    </div>

    {% if config.session.allow_remembering %}
    <div class="auth-field">
        <label class="auth-check">
            <input type="checkbox" name="remember" value="1">
            <span>Remember me</span>
        </label>
    </div>
    {% endif %}

    <button type="submit" class="btn btn-primary auth-submit">Sign In</button>
</form>

<div class="auth-links">
    <a href="/auth/forgot">Forgot password?</a>
</div>
<div class="auth-links">
    {% if config.allow_magic_link %}
        <a href="/auth/magic-link">Sign in with Magic Link</a>
    {% endif %}
    {% if config.allow_magic_link and config.allow_registration %}
        <span class="auth-sep">|</span>
    {% endif %}
    {% if config.allow_registration %}
        <a href="/auth/register">Create an account</a>
    {% endif %}
</div>
        </div>
    </div>
</body>
</html>
