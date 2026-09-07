{# Auth layout: standalone shell for the password reset pages. #}
{# PasswordResetController fetches the page body and renders it here. #}
{# authTitle / authSubtitle are controller-set; content is pre-rendered HTML. #}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ authTitle }}</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/theme/default/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/theme/default/css/pubvana.css">
</head>
<body>
    <div class="auth-shell">
        <div class="auth-card">
            <h1 class="auth-title">{{ authTitle }}</h1>
            {% if authSubtitle %}
            <p class="auth-sub">{{ authSubtitle }}</p>
            {% endif %}
            {! content !}
        </div>
    </div>
</body>
</html>
