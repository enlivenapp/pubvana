{% if comments_enabled %}
<hr>
<section class="mt-4" id="comments">
    <h3>Comments ({{ comments | count }})</h3>

    {% if comments %}
    {% for comment in comments %}
    <div class="card mb-2" style="margin-left: {{ comment.margin_left }}" id="comment-{{ comment.id }}">
        <div class="card-body py-2 px-3">
            <strong>{{ comment.author }}</strong>
            <span class="text-muted small ms-1">{{ comment.date | date('F j, Y') }}</span>
            <div class="mt-1">{! comment.body !}</div>
            {% if comments_open %}
            <button type="button" class="btn btn-sm btn-link p-0 mt-1 comment-reply-btn" data-parent-id="{{ comment.id }}">Reply</button>
            {% endif %}
        </div>
    </div>
    {% endfor %}
    {% else %}
    <p class="text-muted">No comments yet.</p>
    {% endif %}

    {% if comments_open %}
    <div class="mt-4" id="comment-form-wrapper">
        <h5 id="comment-form-title">Leave a Comment</h5>
        <div id="reply-indicator" class="alert alert-info d-none mb-3">
            Replying to a comment — <a href="#" id="cancel-reply">cancel</a>
        </div>
        <form method="post" action="{{ comment_post_url }}" id="comment-form">
            {! csrf_field !}
            <input type="hidden" name="parent_id" id="comment-parent-id" value="">

            {% if comments_is_guest %}
            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" name="guest_name" class="form-control" placeholder="Name *" required>
                </div>
                <div class="col-md-4">
                    <input type="email" name="guest_email" class="form-control" placeholder="Email">
                </div>
                <div class="col-md-4">
                    <input type="url" name="guest_website" class="form-control" placeholder="Website">
                </div>
            </div>
            {% endif %}

            <div class="mb-3">
                <textarea name="body" class="form-control" rows="4" placeholder="Your comment..." required></textarea>
            </div>

            {% captcha 'comments' %}

            <button type="submit" class="btn btn-primary">Post Comment</button>
        </form>
    </div>
    {% elseif comments_closed %}
    <p class="text-muted mt-3">Comments are closed for this post.</p>
    {% else %}
    <p class="text-muted mt-3">Please <a href="/login">log in</a> to leave a comment.</p>
    {% endif %}
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var btns = document.querySelectorAll('.comment-reply-btn');
    var parentInput = document.getElementById('comment-parent-id');
    var indicator = document.getElementById('reply-indicator');
    var formTitle = document.getElementById('comment-form-title');
    var formWrapper = document.getElementById('comment-form-wrapper');

    btns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            parentInput.value = this.getAttribute('data-parent-id');
            indicator.classList.remove('d-none');
            formTitle.textContent = 'Reply';
            formWrapper.scrollIntoView({ behavior: 'smooth' });
        });
    });

    var cancel = document.getElementById('cancel-reply');
    if (cancel) {
        cancel.addEventListener('click', function(e) {
            e.preventDefault();
            parentInput.value = '';
            indicator.classList.add('d-none');
            formTitle.textContent = 'Leave a Comment';
        });
    }
});
</script>
{% endif %}
