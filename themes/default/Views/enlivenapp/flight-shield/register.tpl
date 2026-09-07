<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create your account</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/theme/default/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/theme/default/css/pubvana.css">
</head>
<body>
    <div class="auth-shell">
        <div class="auth-card">
            <h1 class="auth-title">Create your account</h1>
            <p class="auth-sub">Join us</p>
{% if errors %}
    <div class="auth-alert">
        <ul class="auth-error-list">
            {% for err in errors %}
                <li>{{ err }}</li>
            {% endfor %}
        </ul>
    </div>
{% endif %}

<form method="post" action="/auth/register" autocomplete="off">
    {% csrf_field %}

    <div class="auth-field">
        <label class="auth-label" for="username">Username</label>
        <input type="text" id="username" name="username" class="auth-input"
               value="{{ old.username | default('') }}">
    </div>

    <div class="auth-field">
        <label class="auth-label" for="email">Email</label>
        <input type="email" id="email" name="email" class="auth-input" required
               value="{{ old.email | default('') }}">
    </div>

    <div class="auth-field">
        <label class="auth-label" for="password">Password</label>
        <input type="password" id="password" name="password" class="auth-input" required>
    </div>

    <div class="auth-field">
        <label class="auth-label" for="password_confirm">Confirm Password</label>
        <input type="password" id="password_confirm" name="password_confirm" class="auth-input" required>
    </div>

    <button type="submit" class="btn btn-primary auth-submit">Create Account</button>
</form>

<div class="auth-links">
    <a href="/auth/login">Already have an account? Sign in</a>
</div>
        </div>
    </div>
</body>
</html>
