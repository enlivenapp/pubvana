<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/theme/default/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/theme/default/css/pubvana.css">
</head>
<body>
    <div class="auth-shell">
        <div class="auth-card">
            <h1 class="auth-title">Two-Factor Authentication</h1>
            <p class="auth-sub">A verification code has been sent to your email. Enter it below.</p>
{% if error %}
    <div class="auth-alert">{{ error }}</div>
{% endif %}

{% if message %}
    <div class="auth-alert auth-alert-ok">{{ message }}</div>
{% endif %}

<form method="post" action="/auth/2fa/verify">
    {% csrf_field %}

    <div class="auth-field">
        <label class="auth-label" for="token">Verification Code</label>
        <input type="text" id="token" name="token" maxlength="6" class="auth-input auth-code"
               required autofocus autocomplete="one-time-code">
    </div>

    <button type="submit" class="btn btn-primary auth-submit">Verify</button>
</form>

<div class="auth-links">
    <form method="post" action="/auth/2fa/resend">
        {% csrf_field %}
        <button type="submit" class="auth-linklike" id="resendBtn" disabled>
            Resend code (<span id="countdown">5:00</span>)
        </button>
    </form>
</div>

<script>
(function() {
    var seconds = 300;
    var btn = document.getElementById('resendBtn');
    var span = document.getElementById('countdown');
    var timer = setInterval(function() {
        seconds--;
        var m = Math.floor(seconds / 60);
        var s = seconds % 60;
        span.textContent = m + ':' + (s < 10 ? '0' : '') + s;
        if (seconds <= 0) {
            clearInterval(timer);
            btn.disabled = false;
            btn.textContent = 'Resend code';
        }
    }, 1000);
})();
</script>
        </div>
    </div>
</body>
</html>
