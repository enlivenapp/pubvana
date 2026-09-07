{# Alert partial, included from layout.tpl. Renders one-shot session flash messages. #}
{# Bootstrap alert classes, already loaded by the theme. Empty when nothing was flashed. #}
{% if flash %}
<div class="container">
    {% for msg in flash %}
    <div class="alert alert-{{ msg.type }} my-3" role="alert">
        {{ msg.message }}
    </div>
    {% endfor %}
</div>
{% endif %}