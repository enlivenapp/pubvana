{% if show %}
<div class="card mt-4 mb-4 fact-check-summary" data-prompt-version="{{ prompt_version }}">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ti ti-certificate me-2"></i>{{ title }}</h5>
        <span class="badge bg-secondary-lt">{{ overall_verdict_label }}</span>
    </div>
    <div class="card-body">
        {% if stale %}
        <p class="small text-warning mb-2"><i class="ti ti-alert-triangle me-1"></i>The content has been edited since this check was made.</p>
        {% endif %}
        {% if interference %}
        <div class="alert alert-danger py-2 px-3 small mb-3">
            <i class="ti ti-shield-off me-1"></i><strong>Prompt interference flagged.</strong>
            {{ interference_note }}
        </div>
        {% endif %}
        <div class="mb-2 small" style="white-space: pre-wrap;">{{ summary }}</div>
        <div class="mb-2">
            <span class="badge bg-success-lt me-1">{{ counts.supported }} supported</span>
            <span class="badge bg-warning-lt me-1">{{ counts.partially_supported }} partial</span>
            <span class="badge bg-danger-lt me-1">{{ counts.refuted }} refuted</span>
            <span class="badge bg-secondary-lt me-1">{{ counts.unverifiable }} unverifiable</span>
            <span class="badge bg-blue-lt">{{ counts.opinions }} opinions</span>
        </div>
        <p class="small text-secondary mb-0">
            Checked {{ checked_at | date('M j, Y') }} under Pubvana fact-check prompt v{{ prompt_version }}.
            <a href="{{ about_url }}" target="_blank" rel="noopener">How Pubvana fact checking works</a><br>
            Note: AI can make mistakes or miss facts. We encourage you to research the subject yourself.
        </p>
    </div>
</div>
{% endif %}
