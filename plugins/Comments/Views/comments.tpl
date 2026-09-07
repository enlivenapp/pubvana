{% extends 'layout' %}

{% block content %}
<div class="row">
    <div class="col-lg-8">
        {% if comments_enabled %}
        <div class="pv-comments">
            <h2>Comments</h2>

            {% if comments_error %}
            <div class="pv-comments-error">{{ comments_error }}</div>
            {% endif %}

            {% if comments %}
            <ul class="pv-comments-list">
                {% for comment in comments %}
                <li class="pv-comment pv-comment-depth-{{ comment.depth }}">
                    <div class="pv-comment-meta">
                        <strong>{{ comment.author }}</strong>
                        <small>{{ comment.created_at | date('M j, Y g:ia') }}</small>
                    </div>
                    <div class="pv-comment-body">{! comment.body !}</div>
                </li>
                {% endfor %}
            </ul>
            {% else %}
            <p class="pv-comments-empty">No comments yet.</p>
            {% endif %}

            {% if comments_open %}
            <form method="POST" action="{{ comment_post_url }}" class="pv-comment-form" autocomplete="off">
                {% csrf_field %}
                <textarea name="body" rows="4" placeholder="Leave a comment..." required></textarea>
                {% if comments_is_guest %}
                <input type="text" name="guest_name" placeholder="Name" required>
                <input type="email" name="guest_email" placeholder="Email (optional)">
                <input type="text" name="guest_website" placeholder="Website (optional)">
                {% endif %}
                {% captcha 'comments' %}
                <button type="submit">Post Comment</button>
            </form>
            {% endif %}

            {% if comments_closed %}
            <p class="pv-comments-closed">Comments are closed.</p>
            {% endif %}
        </div>
        {% endif %}
    </div>
</div>
{% endblock %}
