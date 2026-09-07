{# Forgot-password body. Rendered into the auth layout by the controller. #}
{% if error %}
    <div class="auth-alert auth-alert-err">{{ error }}</div>
{% endif %}

<form method="post" action="/auth/forgot/send" autocomplete="off">
    {% csrf_field %}

    <div class="auth-field">
        <label class="auth-label" for="email">Email</label>
        <input type="email" id="email" name="email" class="auth-input" required autofocus>
    </div>

    <button type="submit" class="btn btn-primary auth-submit">Send Reset Link</button>
</form>

<div class="auth-links">
    <a href="/auth/login">Back to sign in</a>
</div>
