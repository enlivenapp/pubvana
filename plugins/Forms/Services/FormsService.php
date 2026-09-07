<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Forms\Services;

use Pubvana\Plugins\Forms\Models\Form;
use Pubvana\Plugins\Forms\Models\FormField;
use Pubvana\Plugins\Forms\Models\FormSubmission;
use flight\Engine;

class FormsService
{
    /** @var Engine<object> */
    private Engine $app;
    private Form $forms;
    private FormField $fields;
    private FormSubmission $submissions;

    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param Engine<object>       $app
     * @param array<string, mixed> $config
     */
    public function __construct(\PDO $pdo, Engine $app, array $config = [])
    {
        $this->app = $app;
        $this->forms = new Form($pdo);
        $this->fields = new FormField($pdo);
        $this->submissions = new FormSubmission($pdo);
        $this->config = $config;
    }

    /**
     * @return array{items: array<int, Form>, total: int, page: int, per_page: int}
     */
    public function listForms(int $page = 1, ?int $perPage = null): array
    {
        $perPage ??= (int) ($this->config['per_page'] ?? 25);

        return [
            'items'    => $this->forms->paginate($page, $perPage),
            'total'    => $this->forms->countAll(),
            'page'     => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * @return array<int, Form>
     */
    public function listAllForms(): array
    {
        return $this->forms->listAll();
    }

    public function findForm(int $id): ?Form
    {
        return $this->forms->findById($id);
    }

    public function findPublishedFormBySlug(string $slug): ?Form
    {
        return $this->forms->findPublishedBySlug($slug);
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        return $this->forms->slugExists($slug, $excludeId);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createForm(array $data): Form
    {
        $fieldDefinitions = $this->decodeFieldDefinitions($data['field_definitions'] ?? '[]');
        unset($data['field_definitions']);

        $form = $this->forms->createRecord($data);
        $this->syncFields((int) $form->id, $fieldDefinitions);

        return $form;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateForm(int $id, array $data): ?Form
    {
        $form = $this->forms->findById($id);
        if ($form === null) {
            return null;
        }

        $fieldDefinitions = $this->decodeFieldDefinitions($data['field_definitions'] ?? '[]');
        unset($data['field_definitions']);

        $form->updateRecord($data);
        $this->syncFields($id, $fieldDefinitions);

        return $form;
    }

    public function deleteForm(int $id): bool
    {
        $form = $this->forms->findById($id);
        if ($form === null) {
            return false;
        }

        $form->softDelete();
        return true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFieldDefinitions(int $formId): array
    {
        $definitions = [];
        foreach ($this->fields->forForm($formId) as $field) {
            $definitions[] = [
                'type'        => $field->type,
                'name'        => $field->name,
                'label'       => $field->label,
                'help_text'   => $field->help_text,
                'placeholder' => $field->placeholder,
                'required'    => (bool) $field->is_required,
                'width'       => $field->width,
                'options'     => $this->decodeJsonArray($field->options_json),
            ];
        }

        return $definitions;
    }

    /**
     * @return array{items: array<int, FormSubmission>, total: int, page: int, per_page: int}
     */
    public function listSubmissions(int $page = 1, ?int $formId = null, ?int $perPage = null): array
    {
        $perPage ??= (int) ($this->config['submissions_per_page'] ?? 25);

        return [
            'items'    => $this->submissions->paginate($page, $perPage, $formId),
            'total'    => $this->submissions->countAll($formId),
            'page'     => $page,
            'per_page' => $perPage,
        ];
    }

    public function findSubmission(int $id): ?FormSubmission
    {
        return $this->submissions->findById($id);
    }

    private function routePrefix(): string
    {
        $prefix = rtrim((string) ($this->config['route_prefix'] ?? ''), '/');
        if ($prefix === '') {
            $prefix = '/' . trim($this->config['routePrepend'] ?? 'forms', '/');
        }
        return $prefix;
    }

    /**
     * @return array<string, mixed>
     */
    public function decodeSubmissionPayload(?string $json): array
    {
        return $this->decodeJsonArray($json);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function dashboardCards(): array
    {
        $all = $this->listAllForms();
        $published = count(array_filter($all, static fn (Form $f): bool => $f->status === 'published'));
        $submissions = $this->submissions->countAll();

        return [
            [
                'id'          => 'forms-total',
                'label'       => 'Forms',
                'value'       => count($all),
                'icon'        => 'ti-forms',
                'tone'        => 'primary',
                'group'       => 'forms',
                'href'        => $this->routePrefix(),
                'description' => 'Forms created on the site.',
            ],
            [
                'id'          => 'forms-published',
                'label'       => 'Published',
                'value'       => $published,
                'icon'        => 'ti-circle-check',
                'tone'        => 'success',
                'group'       => 'forms',
                'href'        => $this->routePrefix(),
                'description' => 'Forms live on the site.',
            ],
            [
                'id'          => 'forms-submissions',
                'label'       => 'Submissions',
                'value'       => $submissions,
                'icon'        => 'ti-inbox',
                'tone'        => 'info',
                'group'       => 'forms',
                'href'        => $this->routePrefix() . '/submissions',
                'description' => 'Entries received across all forms.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function defaultFieldDefinitions(): array
    {
        return [
            [
                'type'        => 'text',
                'name'        => 'name',
                'label'       => 'Name',
                'required'    => true,
                'placeholder' => 'Your name',
                'help_text'   => '',
                'width'       => 'full',
                'options'     => [],
            ],
            [
                'type'        => 'email',
                'name'        => 'email',
                'label'       => 'Email',
                'required'    => true,
                'placeholder' => 'you@example.com',
                'help_text'   => '',
                'width'       => 'full',
                'options'     => [],
            ],
            [
                'type'        => 'textarea',
                'name'        => 'message',
                'label'       => 'Message',
                'required'    => true,
                'placeholder' => 'How can we help?',
                'help_text'   => '',
                'width'       => 'full',
                'options'     => [],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, string> $errors
     */
    public function renderPublicForm(Form $form, array $values = [], array $errors = []): string
    {
        $flash = $this->consumeSubmissionFlash((int) $form->id);
        if (!empty($flash)) {
            if (($flash['ok'] ?? false) === true) {
                $values = [];
                $errors = [];
            } else {
                $values = is_array($flash['values'] ?? null) ? $flash['values'] : $values;
                $errors = is_array($flash['errors'] ?? null) ? $flash['errors'] : $errors;
            }
        }

        $fields = $this->fields->forForm((int) $form->id);
        $action = rtrim((string) $this->app->get('flight.base_url'), '/') . $this->routePrefix() . '/submit/' . (int) $form->id;
        $returnUrl = $this->currentRequestPath();

        $html = '';

        if (($flash['ok'] ?? false) === true) {
            $html .= '<div class="pv-form-success">' . htmlspecialchars((string) $form->success_message) . '</div>';
        }

        if (!empty($errors)) {
            $html .= '<div class="pv-form-errors"><ul class="pv-form-errors-list">';
            foreach ($errors as $error) {
                $html .= '<li>' . htmlspecialchars($error) . '</li>';
            }
            $html .= '</ul></div>';
        }

        if (!empty($form->description)) {
            $html .= '<p>' . nl2br(htmlspecialchars((string) $form->description)) . '</p>';
        }

        $html .= '<form method="POST" action="' . htmlspecialchars($action) . '" class="pv-form">';
        $html .= function_exists('csrf_field') ? csrf_field() : '';
        $html .= '<input type="hidden" name="_return_url" value="' . htmlspecialchars($returnUrl) . '">';
        $html .= '<input type="text" name="website" value="" autocomplete="off" tabindex="-1" style="position:absolute;left:-9999px;">';

        foreach ($fields as $field) {
            $name = (string) $field->name;
            $type = (string) $field->type;
            $label = (string) $field->label;
            $placeholder = (string) ($field->placeholder ?? '');
            $required = (int) $field->is_required === 1;
            $value = $values[$name] ?? '';
            $html .= '<div class="pv-form-field pv-form-field-' . htmlspecialchars($type) . '">';
            $html .= '<label class="pv-form-label" for="field-' . htmlspecialchars($name) . '">' . htmlspecialchars($label);
            if ($required) {
                $html .= ' *';
            }
            $html .= '</label>';

            if (in_array($type, ['textarea'], true)) {
                $html .= '<textarea class="pv-form-textarea" id="field-' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '" rows="5"'
                    . ($required ? ' required' : '') . ' placeholder="' . htmlspecialchars($placeholder) . '">'
                    . htmlspecialchars((string) $value) . '</textarea>';
            } elseif (in_array($type, ['select', 'radio', 'checkbox'], true)) {
                $options = $this->decodeJsonArray($field->options_json);
                if ($type === 'select') {
                    $html .= '<select class="pv-form-select" id="field-' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '"'
                        . ($required ? ' required' : '') . '>';
                    $html .= '<option value="">Choose…</option>';
                    foreach ($options as $option) {
                        $selected = (string) $value === (string) $option ? ' selected' : '';
                        $html .= '<option value="' . htmlspecialchars((string) $option) . '"' . $selected . '>' . htmlspecialchars((string) $option) . '</option>';
                    }
                    $html .= '</select>';
                } else {
                    $html .= '<div class="pv-form-choices">';
                    foreach ($options as $index => $option) {
                        $optionId = 'field-' . $name . '-' . $index;
                        $isChecked = $type === 'checkbox'
                            ? (is_array($value) ? in_array((string) $option, array_map('strval', $value), true) : (string) $value === (string) $option)
                            : (string) $value === (string) $option;
                        $checked = $isChecked ? ' checked' : '';
                        $inputName = $type === 'checkbox' ? $name . '[]' : $name;
                        $html .= '<label class="pv-form-choice">';
                        $html .= '<input class="pv-form-choice-input" type="' . ($type === 'radio' ? 'radio' : 'checkbox') . '" id="' . htmlspecialchars($optionId)
                            . '" name="' . htmlspecialchars($inputName) . '" value="' . htmlspecialchars((string) $option) . '"' . $checked
                            . ($required ? ' required' : '') . '>';
                        $html .= '<span class="pv-form-choice-label">' . htmlspecialchars((string) $option) . '</span>';
                        $html .= '</label>';
                    }
                    $html .= '</div>';
                }
            } else {
                $inputType = match ($type) {
                    'email' => 'email',
                    'phone' => 'tel',
                    'hidden' => 'hidden',
                    default => 'text',
                };

                $inputClass = $inputType === 'hidden' ? ' class="pv-form-hidden"' : ' class="pv-form-input"';
                $html .= '<input type="' . $inputType . '" id="field-' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '"'
                    . $inputClass
                    . ' value="' . htmlspecialchars((string) $value) . '"'
                    . ($required ? ' required' : '')
                    . ' placeholder="' . htmlspecialchars($placeholder) . '">';
            }

            if (!empty($field->help_text) && $type !== 'hidden') {
                $html .= '<div class="pv-form-help">' . htmlspecialchars((string) $field->help_text) . '</div>';
            }

            $html .= '</div>';
        }

        $html .= '<button type="submit" class="pv-form-submit">' . htmlspecialchars((string) ($form->submit_label ?: 'Submit')) . '</button>';
        $html .= '</form>';

        return $html;
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function renderTag(array $args): string
    {
        $form = $this->resolvePublishedFormFromArgs($args);
        if ($form === null) {
            return '';
        }

        return $this->renderPublicForm($form);
    }

    public function renderContentEmbeds(string $content): string
    {
        $content = preg_replace_callback('/\{\{\s*forms\s*:\s*(.*?)\s*\}\}/i', function (array $matches): string {
            return $this->renderEmbeddedFormFromAttributeString($matches[1]);
        }, $content) ?? $content;

        $content = preg_replace_callback('/\{%\s*forms\s+(.*?)\s*%\}/i', function (array $matches): string {
            return $this->renderEmbeddedFormFromTagString($matches[1]);
        }, $content) ?? $content;

        return $content;
    }

    public function renderBlock(mixed $formId = null, mixed $formSlug = null): string
    {
        if ($formId !== null && $formId !== '' && ctype_digit((string) $formId)) {
            $form = $this->findForm((int) $formId);
            if ($form !== null && ($form->status ?? '') === 'published') {
                return $this->renderPublicForm($form);
            }
        }

        if ($formSlug !== null && $formSlug !== '') {
            $form = $this->findPublishedFormBySlug((string) $formSlug);
            if ($form !== null) {
                return $this->renderPublicForm($form);
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed>       $values
     * @param array<string, mixed>       $requestMeta
     * @return array<string, mixed>
     */
    public function submitForm(Form $form, array $values, array $requestMeta = []): array
    {
        unset($values['_csrf_token']);
        unset($values['_return_url']);

        if (!empty($values['website'])) {
            return ['ok' => true, 'errors' => [], 'values' => []];
        }
        unset($values['website']);

        if ($this->isRateLimited((int) $form->id)) {
            return [
                'ok'     => false,
                'errors' => ['Please wait a moment before submitting again.'],
                'values' => $values,
            ];
        }

        $fields = $this->fields->forForm((int) $form->id);
        $errors = [];
        $clean = [];

        foreach ($fields as $field) {
            $name = (string) $field->name;
            $type = (string) $field->type;
            $required = (int) $field->is_required === 1;
            $value = $values[$name] ?? null;
            $options = $this->decodeJsonArray($field->options_json);
            $normalized = $this->sanitizeSubmittedValue($field, $value);

            if ($required && $this->isEmptySubmittedValue($normalized)) {
                $errors[] = ($field->label ?: $name) . ' is required.';
                continue;
            }

            if (!$this->isEmptySubmittedValue($normalized) && $type === 'email' && !filter_var((string) $normalized, FILTER_VALIDATE_EMAIL)) {
                $errors[] = ($field->label ?: $name) . ' must be a valid email address.';
                continue;
            }

            if (!$this->isEmptySubmittedValue($normalized) && in_array($type, ['select', 'radio'], true)) {
                if (!in_array((string) $normalized, array_map('strval', $options), true)) {
                    $errors[] = ($field->label ?: $name) . ' contains an invalid selection.';
                    continue;
                }
            }

            if (!$this->isEmptySubmittedValue($normalized) && $type === 'checkbox' && !empty($options)) {
                $normalizedValues = is_array($normalized) ? $normalized : [(string) $normalized];
                $allowed = array_map('strval', $options);

                foreach ($normalizedValues as $item) {
                    if (!in_array((string) $item, $allowed, true)) {
                        $errors[] = ($field->label ?: $name) . ' contains an invalid selection.';
                        continue 2;
                    }
                }
            }

            $clean[$name] = $normalized;
        }

        if (!empty($errors)) {
            return [
                'ok'     => false,
                'errors' => $errors,
                'values' => $values,
            ];
        }

        $this->submissions->createRecord([
            'form_id'      => (int) $form->id,
            'status'       => 'received',
            'ip_address'   => $requestMeta['ip_address'] ?? null,
            'user_agent'   => $requestMeta['user_agent'] ?? null,
            'referrer_url' => $requestMeta['referrer'] ?? null,
            'payload_json' => json_encode($clean, JSON_UNESCAPED_SLASHES),
        ]);

        $this->markRateLimited((int) $form->id);
        $this->dispatchNotifications($form, $clean);

        return [
            'ok'     => true,
            'errors' => [],
            'values' => [],
        ];
    }

    public function normalizeReturnUrl(?string $returnUrl, ?string $referrer = null): string
    {
        $candidate = $returnUrl ?: $referrer ?: '/';

        if (preg_match('#^https?://#i', $candidate)) {
            $base = (string) ($this->app->get('flight.base_url') ?? '');
            if ($base !== '' && str_starts_with($candidate, rtrim($base, '/'))) {
                $candidate = substr($candidate, strlen(rtrim($base, '/')));
            } else {
                return '/';
            }
        }

        if (!str_starts_with($candidate, '/')) {
            $candidate = '/' . ltrim($candidate, '/');
        }

        return $candidate;
    }

    /**
     * @param array<string, mixed> $result Submit result as returned by submitForm()
     */
    public function storeSubmissionFlash(int $formId, array $result): void
    {
        $this->startSession();
        $_SESSION['pubvana_forms_flash'][$formId] = $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function consumeSubmissionFlash(int $formId): array
    {
        $this->startSession();
        $flash = $_SESSION['pubvana_forms_flash'][$formId] ?? [];
        unset($_SESSION['pubvana_forms_flash'][$formId]);
        return is_array($flash) ? $flash : [];
    }

    /**
     * @param array<int, array<string, mixed>> $definitions
     */
    private function syncFields(int $formId, array $definitions): void
    {
        $this->fields->deleteForForm($formId);

        foreach (array_values($definitions) as $index => $definition) {
            if (empty($definition['name']) || empty($definition['type'])) {
                continue;
            }

            $this->fields->createRecord([
                'form_id'      => $formId,
                'type'         => (string) $definition['type'],
                'name'         => (string) $definition['name'],
                'label'        => (string) ($definition['label'] ?? $definition['name']),
                'help_text'    => $definition['help_text'] ?? null,
                'placeholder'  => $definition['placeholder'] ?? null,
                'is_required'  => !empty($definition['required']) ? 1 : 0,
                'width'        => $definition['width'] ?? 'full',
                'options_json' => json_encode($definition['options'] ?? [], JSON_UNESCAPED_SLASHES),
                'sort_order'   => $index + 1,
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decodeFieldDefinitions(string $json): array
    {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<int|string, mixed> $args
     */
    private function resolvePublishedFormFromArgs(array $args): ?Form
    {
        if (count($args) === 1) {
            $first = $args[0];
            if (is_numeric($first)) {
                $form = $this->findForm((int) $first);
                return ($form !== null && ($form->status ?? '') === 'published') ? $form : null;
            }

            return $this->findPublishedFormBySlug((string) $first);
        }

        if (count($args) >= 2) {
            $key = strtolower((string) $args[0]);
            $value = $args[1];

            if ($key === 'id' && is_numeric($value)) {
                $form = $this->findForm((int) $value);
                return ($form !== null && ($form->status ?? '') === 'published') ? $form : null;
            }

            if ($key === 'slug') {
                return $this->findPublishedFormBySlug((string) $value);
            }
        }

        return null;
    }

    private function renderEmbeddedFormFromAttributeString(string $raw): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $raw) ?? '');

        if (preg_match('/^id\s+([0-9]+)$/i', $normalized, $matches)) {
            return $this->renderTag(['id', $matches[1]]);
        }

        if (preg_match('/^slug\s+[\'"]?([^\'"]+)[\'"]?$/i', $normalized, $matches)) {
            return $this->renderTag(['slug', $matches[1]]);
        }

        return '';
    }

    private function renderEmbeddedFormFromTagString(string $raw): string
    {
        preg_match_all('/"[^"]*"|\'[^\']*\'|\S+/', $raw, $matches);
        $tokens = array_map(static function (string $token): string {
            return trim($token, " \t\n\r\0\x0B'\"");
        }, $matches[0]);

        return $this->renderTag($tokens);
    }

    private function currentRequestPath(): string
    {
        $uri = (string) ($this->app->request()->getVar('REQUEST_URI') ?? '/');
        return $uri !== '' ? $uri : '/';
    }

    private function isRateLimited(int $formId): bool
    {
        $this->startSession();
        $seconds = (int) ($this->config['rate_limit_seconds'] ?? 10);
        if ($seconds <= 0) {
            return false;
        }

        $key = 'form_' . $formId;
        $last = $_SESSION['pubvana_forms_rate'][$key] ?? null;
        return is_int($last) && (time() - $last) < $seconds;
    }

    private function markRateLimited(int $formId): void
    {
        $this->startSession();
        $_SESSION['pubvana_forms_rate']['form_' . $formId] = time();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function dispatchNotifications(Form $form, array $payload): void
    {
        $emails = array_filter(array_map('trim', explode(',', (string) ($form->notification_emails ?? ''))));
        if (empty($emails)) {
            return;
        }

        $subject = 'New form submission: ' . $form->name;
        $lines = [];
        foreach ($payload as $key => $value) {
            $lines[] = $key . ': ' . (is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_SLASHES));
        }
        $body = nl2br(htmlspecialchars(implode("\n", $lines)));

        foreach ($emails as $email) {
            try {
                $this->app->mailer()->sendHtml($email, $subject, $body);
            } catch (\Throwable) {
                // Intentionally swallow delivery failures.
            }
        }
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }

    private function sanitizeSubmittedValue(object $field, mixed $value): mixed
    {
        $type = (string) ($field->type ?? 'text');

        if (is_array($value)) {
            $sanitized = [];

            foreach ($value as $item) {
                if (is_array($item) || is_object($item)) {
                    continue;
                }

                $clean = $this->sanitizeScalarValue($type, $item);
                if ($clean !== null && $clean !== '') {
                    $sanitized[] = $clean;
                }
            }

            return $sanitized;
        }

        if (is_object($value)) {
            return null;
        }

        return $this->sanitizeScalarValue($type, $value);
    }

    private function sanitizeScalarValue(string $type, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value) || is_int($value) || is_float($value)) {
            $value = (string) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return match ($type) {
            'textarea' => $this->purifySubmittedHtml($value),
            'email'    => trim((string) filter_var($value, FILTER_SANITIZE_EMAIL)),
            default    => trim(strip_tags($value)),
        };
    }

    private function purifySubmittedHtml(string $value): string
    {
        if (!class_exists(\HTMLPurifier::class)) {
            return strip_tags($value);
        }

        $config = \HTMLPurifier_Config::createDefault();
        return trim((new \HTMLPurifier($config))->purify($value));
    }

    private function isEmptySubmittedValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_array($value)) {
            return count($value) === 0;
        }

        return $value === '';
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonArray(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
