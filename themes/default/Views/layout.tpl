{# Master page shell. Every public page template extends this layout. #}
{# Theme assets load straight from /assets/theme/... (served by AssetService, never copied into public/). #}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {# Raw output: plugin-contributed <head> markup and stylesheets. Trusted HTML, {{ }} would escape it. #}
    {# Plugin CSS loads FIRST. The theme's own stylesheets below load after it, so theme rules override plugin rules on ties. #}
    {! header !}
    <link rel="stylesheet" href="/assets/theme/default/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/theme/default/css/pubvana.css">
    {# Vision block: an inheritance slot. A child page template that declares {% block head_extra %} fills this; empty by default. #}
    {% block head_extra %}{% endblock %}
</head>
<body>

    {# Include: renders partials/navbar.tpl inline. Included templates see the same variables. #}
    {% include 'partials/navbar' %}

    {# Theme option branch: the hero banner renders only when the site owner enables it. #}
    {% if theme_options.hero.show %}
    {% include 'partials/hero' %}
    {% endif %}

    {# Filter: default('1') covers a missing option. Renders only when breadcrumbs exist for this page. #}
    {% if theme_options.breadcrumbs.enabled | default('1') and breadcrumbs %}
    {% include 'partials/breadcrumbs' %}
    {% endif %}

    {# Region: content blocks the site owner placed via Admin > Appearance > Themes > Regions. #}
    {# A region with nothing placed in it prints nothing, so it is safe to always output. #}
    {% region 'before-content' %}

    {# Include: one-shot flash messages (login notices, form feedback, etc.), before the page body. #}
    {% include 'partials/alerts' %}

    {# Vision block: the page body. Every page template (home, post, page, ...) overrides this one slot. #}
    {# Each page template renders the after-content region itself, so blocks land inside the #}
    {# content column on split pages instead of full width below the whole layout. #}
    <main class="container my-4">
        {% block content %}{% endblock %}
    </main>

    {# Include: footer columns, footer region, and the copyright line. #}
    {% include 'partials/footer' %}

    <script src="/assets/theme/default/js/bootstrap.bundle.min.js"></script>
    {# Raw output: plugin scripts, pre-assembled into one block by the controller (mirror of {! header !}). #}
    {! scripts_footer !}
    {# Vision block: inheritance slot for child pages to append scripts before </body>. #}
    {% block scripts_extra %}{% endblock %}
</body>
</html>
