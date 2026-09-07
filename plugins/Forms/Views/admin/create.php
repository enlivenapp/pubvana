<?php /** @var string $pageTitle @var string $adminBase */ ?>

<div class="d-flex align-items-center justify-content-end mb-4">
    <a href="<?= $adminBase ?>" class="btn btn-outline-secondary">Back</a>
</div>

<form method="POST" action="<?= $adminBase ?>/store">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="name">Name</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="slug">Slug</label>
                        <input type="text" name="slug" id="slug" class="form-control" placeholder="Auto-generated from name">
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="4"></textarea>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Builder</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2 flex-wrap mb-3">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-add-field="text">Text</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-add-field="textarea">Textarea</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-add-field="email">Email</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-add-field="phone">Phone</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-add-field="select">Select</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-add-field="radio">Radio</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-add-field="checkbox">Checkbox</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-add-field="hidden">Hidden</button>
                    </div>
                    <input type="hidden" name="field_definitions" id="field_definitions" value="<?= htmlspecialchars($fieldsJson ?? '[]') ?>">
                    <div id="builder-empty" class="alert alert-info">
                        Click a button above to start building your form.
                    </div>
                    <div id="builder-fields" class="d-grid gap-3"></div>
                    <p class="text-secondary small mt-3 mb-0">Drag fields by their header to reorder them.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Settings</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="status">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="draft" selected>Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="submit_label">Submit Label</label>
                        <input type="text" name="submit_label" id="submit_label" class="form-control" value="Submit">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="notification_emails">Notification Emails</label>
                        <textarea name="notification_emails" id="notification_emails" class="form-control" rows="3" placeholder="one@example.com, two@example.com"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="success_message">Success Message</label>
                        <textarea name="success_message" id="success_message" class="form-control" rows="4">Thanks, your submission has been received.</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Template Tags</label>
                        <input type="text" class="form-control mb-2" value="{% forms 'slug' 'your-form-slug' %}" readonly>
                        <input type="text" class="form-control" value="{% forms 'id' 123 %}" readonly>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Create Form</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var nameEl = document.getElementById('name');
    var slugEl = document.getElementById('slug');
    var slugEdited = false;
    var fieldsInput = document.getElementById('field_definitions');
    var fieldsRoot = document.getElementById('builder-fields');
    var emptyState = document.getElementById('builder-empty');
    var dragState = null;

    var fields = [];
    try {
        fields = JSON.parse(fieldsInput.value || '[]');
    } catch (e) {
        fields = [];
    }

    function slugify(value) {
        return (value || '').toLowerCase().trim()
            .replace(/&/g, 'and')
            .replace(/[^a-z0-9\\s-]/g, '')
            .replace(/[\\s]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    }

    function needsOptions(type) {
        return ['select', 'radio', 'checkbox'].indexOf(type) !== -1;
    }

    function defaultField(type) {
        return {
            type: type,
            name: slugify(type + '-' + (fields.length + 1)),
            label: type.charAt(0).toUpperCase() + type.slice(1),
            help_text: '',
            placeholder: '',
            required: false,
            width: 'full',
            options: needsOptions(type) ? ['Option 1', 'Option 2'] : []
        };
    }

    function sync() {
        fieldsInput.value = JSON.stringify(fields);
    }

    function render() {
        fieldsRoot.innerHTML = '';
        if (emptyState) {
            emptyState.style.display = fields.length === 0 ? '' : 'none';
        }
        fields.forEach(function(field, index) {
            var card = document.createElement('div');
            card.className = 'card';
            card.dataset.index = index;
            card.innerHTML = `
                <div class="card-header cursor-move">
                    <div class="w-100 d-flex justify-content-between align-items-center">
                        <strong>${field.label || field.name || ('Field ' + (index + 1))}</strong>
                        <div class="btn-list">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-move-up="${index}">Up</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-move-down="${index}">Down</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-remove="${index}">Remove</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" data-bind="type" data-index="${index}">
                                ${['text','textarea','email','phone','select','radio','checkbox','hidden'].map(function(type) {
                                    return '<option value="' + type + '"' + (field.type === type ? ' selected' : '') + '>' + type + '</option>';
                                }).join('')}
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" data-bind="name" data-index="${index}" value="${field.name || ''}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Label</label>
                            <input type="text" class="form-control" data-bind="label" data-index="${index}" value="${field.label || ''}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Placeholder</label>
                            <input type="text" class="form-control" data-bind="placeholder" data-index="${index}" value="${field.placeholder || ''}">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Help Text</label>
                            <input type="text" class="form-control" data-bind="help_text" data-index="${index}" value="${field.help_text || ''}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Width</label>
                            <select class="form-select" data-bind="width" data-index="${index}">
                                <option value="full"${field.width === 'full' ? ' selected' : ''}>Full</option>
                                <option value="half"${field.width === 'half' ? ' selected' : ''}>Half</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3 ${needsOptions(field.type) ? '' : 'd-none'}" data-options-wrap="${index}">
                            <label class="form-label">Options</label>
                            <textarea class="form-control" rows="3" data-bind="options" data-index="${index}">${(field.options || []).join("\\n")}</textarea>
                            <div class="form-hint">One option per line.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-check">
                                <input type="checkbox" class="form-check-input" data-bind="required" data-index="${index}" ${field.required ? 'checked' : ''}>
                                <span class="form-check-label">Required</span>
                            </label>
                        </div>
                    </div>
                </div>
            `;
            fieldsRoot.appendChild(card);
        });
        sync();
    }

    function startDrag(card, startY) {
        var rect = card.getBoundingClientRect();
        var placeholder = document.createElement('div');
        placeholder.className = 'card border border-primary';
        placeholder.style.height = rect.height + 'px';
        placeholder.style.background = 'rgba(13, 110, 253, 0.08)';
        placeholder.style.borderStyle = 'dashed';
        placeholder.style.borderWidth = '2px';
        placeholder.innerHTML = '<div class="card-body d-flex align-items-center justify-content-center text-primary fw-semibold">Drop field here</div>';

        card.parentNode.insertBefore(placeholder, card.nextSibling);

        dragState = {
            card: card,
            placeholder: placeholder,
            startIndex: Number(card.dataset.index),
            offsetY: startY - rect.top
        };

        card.style.width = rect.width + 'px';
        card.style.position = 'fixed';
        card.style.left = rect.left + 'px';
        card.style.top = rect.top + 'px';
        card.style.zIndex = '2000';
        card.style.pointerEvents = 'none';
        card.classList.add('shadow-lg');
        card.classList.add('opacity-75');
    }

    function moveDrag(clientY) {
        if (!dragState) return;

        dragState.card.style.top = (clientY - dragState.offsetY) + 'px';

        var cards = Array.prototype.slice.call(fieldsRoot.querySelectorAll('.card')).filter(function (item) {
            return item !== dragState.card;
        });

        var inserted = false;
        cards.forEach(function (item) {
            var rect = item.getBoundingClientRect();
            if (!inserted && clientY < rect.top + rect.height / 2) {
                fieldsRoot.insertBefore(dragState.placeholder, item);
                inserted = true;
            }
        });

        if (!inserted) {
            fieldsRoot.appendChild(dragState.placeholder);
        }

        var threshold = 120;
        var amount = 20;
        if (clientY < threshold) {
            window.scrollBy(0, -amount);
        } else if (window.innerHeight - clientY < threshold) {
            window.scrollBy(0, amount);
        }
    }

    function endDrag() {
        if (!dragState) return;

        var placeholderIndex = Array.prototype.indexOf.call(fieldsRoot.children, dragState.placeholder);
        var moved = fields.splice(dragState.startIndex, 1)[0];
        fields.splice(Math.max(0, placeholderIndex), 0, moved);

        dragState.card.removeAttribute('style');
        dragState.card.classList.remove('shadow-lg');
        dragState.card.classList.remove('opacity-75');
        dragState.placeholder.remove();
        dragState = null;

        sync();
        render();
    }

    fieldsRoot.addEventListener('input', function (e) {
        var bind = e.target.dataset.bind;
        var index = Number(e.target.dataset.index);
        if (bind === undefined || Number.isNaN(index)) return;

        if (bind === 'options') {
            fields[index][bind] = e.target.value.split(/\\n/).map(function (item) {
                return item.trim();
            }).filter(Boolean);
        } else {
            fields[index][bind] = e.target.value;
        }

        if (bind === 'type' && !needsOptions(e.target.value)) {
            fields[index].options = [];
        } else if (bind === 'type' && needsOptions(e.target.value) && (!Array.isArray(fields[index].options) || !fields[index].options.length)) {
            fields[index].options = ['Option 1', 'Option 2'];
        }

        sync();
        render();
    });

    fieldsRoot.addEventListener('change', function (e) {
        var bind = e.target.dataset.bind;
        var index = Number(e.target.dataset.index);
        if (bind === 'required' && !Number.isNaN(index)) {
            fields[index].required = !!e.target.checked;
            sync();
        }
    });

    fieldsRoot.addEventListener('click', function (e) {
        var removeIndex = e.target.dataset.remove;
        var upIndex = e.target.dataset.moveUp;
        var downIndex = e.target.dataset.moveDown;

        if (removeIndex !== undefined) {
            fields.splice(Number(removeIndex), 1);
            sync();
            render();
        } else if (upIndex !== undefined) {
            var i = Number(upIndex);
            if (i > 0) {
                var item = fields.splice(i, 1)[0];
                fields.splice(i - 1, 0, item);
                sync();
                render();
            }
        } else if (downIndex !== undefined) {
            var j = Number(downIndex);
            if (j < fields.length - 1) {
                var downItem = fields.splice(j, 1)[0];
                fields.splice(j + 1, 0, downItem);
                sync();
                render();
            }
        }
    });

    document.querySelectorAll('[data-add-field]').forEach(function (button) {
        button.addEventListener('click', function () {
            fields.push(defaultField(button.dataset.addField));
            sync();
            render();
        });
    });

    fieldsRoot.addEventListener('mousedown', function (e) {
        var header = e.target.closest('.card-header');
        if (!header) return;
        var card = header.closest('.card');
        if (!card) return;
        if (e.target.closest('button, input, select, textarea, label')) return;

        e.preventDefault();
        startDrag(card, e.clientY);
    });

    document.addEventListener('mousemove', function (e) {
        moveDrag(e.clientY);
    });

    document.addEventListener('mouseup', function () {
        endDrag();
    });

    slugEl.addEventListener('input', function () {
        slugEdited = this.value !== '';
    });

    nameEl.addEventListener('input', function () {
        if (slugEdited) return;
        slugEl.value = slugify(this.value);
    });

    render();
});
</script>
