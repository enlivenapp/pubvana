# Forms

Form builder for Pubvana. Create forms in the admin (under **Content → Forms**), embed them on the public site, and collect and review submissions.

Three tables are used:

- `forms` — a form's name, slug, status (`draft`/`published`), submit label, success message, and notification emails. Soft-deleted via `deleted_at`.
- `form_fields` — the fields on a form (`type`, `name`, `label`, `placeholder`, `is_required`, `width`, `options_json`), ordered by `sort_order`.
- `form_submissions` — each submission's IP, user agent, referrer, and JSON `payload_json`.

## Embedding a form

Forms are published opt-in: a form only renders when its status is `published`.

### In rich content (pages/posts)

The plugin hooks the `content.render` chain, so shortcodes inside page/post bodies render automatically:

- `{% forms 'slug' 'contact' %}` / `{% forms 'id' 123 %}`
- `{{ forms: slug "contact" }}` / `{{ forms: id 1 }}`

### As a region block

The block provider `pubvana.forms.form` (label **Form**) is available in the region system under **Appearance → Regions**. It takes `title`, `form_id`, and `form_slug` options and renders a published form determined by id (preferred) or slug.

### From your own code

```php
$html = $app->forms()->renderPublicForm($form);            // a full, self-contained form
$html = $app->forms()->renderBlock($formId, $formSlug);    // block provider, published only
$html = $app->forms()->renderTag(['slug' => 'contact']);   // tag renderer
```

The rendered HTML POSTs to `/forms/submit/{id}` and includes a `_return_url` hidden field to control where the visitor lands after submission.

In a Vision template the rendered HTML is output directly:

```twig
{! content !}
```

## Submissions

Every submission is stored, including the source form (`form_id`), the visitor's IP/user agent/referrer, and the field payload as JSON. Review them under **Content → Forms → Submissions**. Exporting per form is available from the submissions list.

## Security and spam controls

- A hidden honeypot field (`website`): if a bot fills it, the submission is silently dropped and never stored.
- A per-form session rate limit (default every 10 seconds, `rate_limit_seconds`; `0` disables) between submissions from the same session.
- Optional captcha: turn on the "Public forms" area under **Settings → Captcha** (hCaptcha or reCAPTCHA v2, configured site-wide). The widget is appended to every published form and the token is verified server-side after the rate limit, before field validation; a rejected captcha is reported like any other validation error.
- Textarea content is purified with HTMLPurifier when available, otherwise `strip_tags`.
- `_return_url` is normalized to a same-site path to prevent open redirects.

## Emails

If a form lists `notification_emails`, each submission is emailed to those addresses via the core mailer. Delivery failures are swallowed so a mail hiccup never breaks the submission.

## Services

The Forms service is registered on the engine as `$app->forms()`:

```php
$app->forms()->listForms($page);                    // paginated admin form list
$app->forms()->findForm($id);                       // one form, or null
$app->forms()->findPublishedFormBySlug($slug);      // one published form, or null
$app->forms()->slugExists($slug, $excludeId);       // bool
$app->forms()->createForm($data);                   // Form
$app->forms()->updateForm($id, $data);              // Form or null
$app->forms()->deleteForm($id);                     // bool (soft delete)
$app->forms()->getFieldDefinitions($formId);        // array of fields
$app->forms()->listSubmissions($page, $formId, $perPage);   // paginated
$app->forms()->findSubmission($id);                 // one submission, or null
$app->forms()->renderPublicForm($form, $values, $errors);   // HTML
$app->forms()->submitForm($form, $values, $requestMeta);    // array response
```

## Permissions

Managing forms and submissions requires the `forms.manage` permission (seeded on install).

## Translations

Not yet available — labels are currently hardcoded in the views.
