{# Status message body. Rendered into the auth layout by the controller. #}
{# The layout carries the page title; error/forgotUrl switch the styling. #}
<div class="auth-alert {% if error %}auth-alert-err{% else %}auth-alert-ok{% endif %}">
    <div>{{ text }}</div>
</div>

<div class="auth-links">
    {% if forgotUrl %}
        <a href="/auth/forgot">Request a new link</a>
        <span class="auth-sep">|</span>
    {% endif %}
    <a href="/auth/login">Back to sign in</a>
</div>
