{# Reset-password body. Token mode keeps the token in a hidden field; #}
{# session mode (forced reset) shows no token UI. Rendered into the layout. #}
{% if error %}
    <div class="auth-alert auth-alert-err">{{ error }}</div>
{% endif %}

{% if sessionMode %}
<p class="auth-note">Your account has a password reset pending. Choose a new password to continue.</p>
{% endif %}

<form method="post" action="/auth/reset-password/process">
    {% csrf_field %}
    {% if token %}
        <input type="hidden" name="token" value="{{ token }}">
    {% endif %}

    <div class="auth-field">
        <label class="auth-label" for="password">New Password</label>
        <input type="password" name="password" id="password" class="auth-input" required autofocus
               autocomplete="new-password">
    </div>

    <div class="auth-field">
        <label class="auth-label" for="password_confirm">Confirm New Password</label>
        <input type="password" name="password_confirm" id="password_confirm" class="auth-input" required
               autocomplete="new-password">
    </div>

    <button type="submit" class="btn btn-primary auth-submit">Set New Password</button>
</form>

{% if not sessionMode %}
<div class="auth-links">
    <a href="/auth/forgot">Request a new link</a>
</div>
{% endif %}
