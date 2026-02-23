<div id="pubvana-toc" class="widget toc-widget d-none">
    <?php if (!empty($title)): ?>
        <h4 class="widget-title"><?= esc($title) ?></h4>
    <?php endif; ?>
    <nav id="pubvana-toc-nav"></nav>
</div>

<script>
(function () {
    var minHeadings = <?= (int) ($min_headings ?? 2) ?>;
    var maxDepth    = <?= json_encode($max_depth ?? 'h3') ?>;

    var selectorMap = { h2: 'h2', h3: 'h2, h3', h4: 'h2, h3, h4' };
    var selector    = '.post-content ' + (selectorMap[maxDepth] || 'h2, h3').split(', ').join(', .post-content ');

    document.addEventListener('DOMContentLoaded', function () {
        var headings = document.querySelectorAll(selector);
        if (headings.length < minHeadings) return;

        // Ensure each heading has an id
        headings.forEach(function (h, i) {
            if (!h.id) {
                h.id = 'toc-' + h.tagName.toLowerCase() + '-' + i + '-' +
                       h.textContent.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
            }
        });

        // Build nested list
        var levels    = { H2: 1, H3: 2, H4: 3 };
        var rootUl    = document.createElement('ul');
        rootUl.className = 'list-unstyled mb-0';
        var stack     = [{ ul: rootUl, level: 0 }];

        headings.forEach(function (h) {
            var level = levels[h.tagName] || 1;

            // Pop stack until we find a parent whose level < current
            while (stack.length > 1 && stack[stack.length - 1].level >= level) {
                stack.pop();
            }

            var li = document.createElement('li');
            li.style.paddingLeft = ((level - 1) * 12) + 'px';
            var a  = document.createElement('a');
            a.href        = '#' + h.id;
            a.textContent = h.textContent;
            a.className   = 'text-decoration-none small';
            li.appendChild(a);

            var parentUl = stack[stack.length - 1].ul;
            parentUl.appendChild(li);

            var subUl = document.createElement('ul');
            subUl.className = 'list-unstyled mb-0';
            li.appendChild(subUl);
            stack.push({ ul: subUl, level: level });
        });

        document.getElementById('pubvana-toc-nav').appendChild(rootUl);
        document.getElementById('pubvana-toc').classList.remove('d-none');
    });
}());
</script>
