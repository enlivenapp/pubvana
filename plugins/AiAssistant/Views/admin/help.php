<?php
/**
 * AI Assistant help for site administrators.
 *
 * Written for an admin who has never touched an API: plain language,
 * a clear setup walkthrough, and everyday descriptions of what each
 * grant allows. The developer reference stays tucked away at the bottom.
 *
 * @var string $pageTitle
 * @var string $adminBase      admin URL base for this plugin
 * @var array  $helpGroups AiService::helpGroups() display groups
 */

$apiBase = rtrim((string) \Flight::app()->pluginLoader()->routePrefix('pubvana/ai'), '/');
?>

<div class="alert alert-info" role="alert">
    <div class="d-flex">
        <i class="ti ti-robot icon alert-icon"></i>
        <div>
            This plugin allows an AI assistant you trust (yeah, we know) write to your site on your
            behalf, for example, to publish articles, tidy up menus, or
            moderate comments. You stay in charge: the assistant can only do
            the things you check off below, and it signs in with a key
            you create and can revoke at any time.
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Getting started (4 steps)</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <div class="d-flex gap-2">
                    <span class="badge bg-primary-lt rounded-circle align-self-start mt-1">1</span>
                    <div>
                        <div class="fw-semibold">Create a key</div>
                        <div class="text-secondary small">On the <a href="<?= $adminBase ?>/manage">Manage</a> page, give the key a name you'll recognise later, like "Blog assistant".</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="d-flex gap-2">
                    <span class="badge bg-primary-lt rounded-circle align-self-start mt-1">2</span>
                    <div>
                        <div class="fw-semibold">Copy the startup text</div>
                        <div class="text-secondary small">Generating the key shows a one-time, copy-ready "AI startup text" with the key and the API guide link. Copy the whole message; it is shown only once.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="d-flex gap-2">
                    <span class="badge bg-primary-lt rounded-circle align-self-start mt-1">3</span>
                    <div>
                        <div class="fw-semibold">Choose its permissions</div>
                        <div class="text-secondary small">Tick the boxes for what the assistant may do. Unticked boxes are denied, even reading.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="d-flex gap-2">
                    <span class="badge bg-primary-lt rounded-circle align-self-start mt-1">4</span>
                    <div>
                        <div class="fw-semibold">Paste it to your assistant</div>
                        <div class="text-secondary small">Paste the whole startup text you copied into your AI tool. It tells the assistant where to fetch the API guide and carries the key, so it can get started on its own.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">What each permission means</h3>
    </div>
    <div class="card-body">
        <p class="text-secondary">
            These are the "tick boxes" you see next to each key. They're written
            in plain English here.
        </p>
        <?php foreach ($helpGroups as $group => $permissions): ?>
            <h5 class="mt-3 mb-2 text-uppercase text-secondary small fw-semibold">
                <?= htmlspecialchars(ucfirst($group)) ?>
            </h5>
            <div class="row g-2">
                <?php foreach ($permissions as $permission => $meta): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="border rounded p-2 h-100">
                            <div class="fw-semibold">
                                <i class="ti ti-check me-1 text-success"></i>
                                <?= htmlspecialchars($meta['label']) ?>
                            </div>
                            <div class="text-secondary small"><?= htmlspecialchars($meta['summary']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title">Fact checking</h3>
        <a href="<?= $adminBase ?>/fact-checks" class="btn btn-sm btn-outline-primary">Open Fact Checking</a>
    </div>
    <div class="card-body">
        <p class="text-secondary">
            Fact checking lets your AI assistant verify the claims in your posts and pages and file a
            structured report: findings, per-claim verdicts (supported, partially supported, refuted,
            unverifiable), facts separated from opinion, and cited sources.
        </p>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="d-flex gap-2">
                    <i class="ti ti-certificate text-primary mt-1"></i>
                    <div>
                        <div class="fw-semibold">A versioned prompt, not vibes</div>
                        <div class="text-secondary small">Your assistant fetches an integrity prompt from this site before every check. You accept its terms once per version on the Fact Checking page; when the prompt is updated, the service pauses until you re-accept.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-2">
                    <i class="ti ti-toggle-switch text-primary mt-1"></i>
                    <div>
                        <div class="fw-semibold">One switch, not more checkboxes</div>
                        <div class="text-secondary small">When fact checking is on, every enabled key can read and submit fact checks. When it is off, the endpoints refuse everything. Enabling needs the terms accepted and at least one enabled key.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-2">
                    <i class="ti ti-shield-check text-primary mt-1"></i>
                    <div>
                        <div class="fw-semibold">Circumvention voids the service</div>
                        <div class="text-secondary small">The terms say only the Pubvana prompt governs the check. Instructions from the article, or from whoever is driving the AI session, that try to alter or skip it void the agreement: the assistant is required to refuse.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-2">
                    <i class="ti ti-flag text-primary mt-1"></i>
                    <div>
                        <div class="fw-semibold">Interference gets flagged, not obeyed</div>
                        <div class="text-secondary small">If content tries to steer its own fact check, the report flags the attempt and quotes it, on the admin pages and on the public block.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-2">
                    <i class="ti ti-layout-sidebar text-primary mt-1"></i>
                    <div>
                        <div class="fw-semibold">Where reports show up</div>
                        <div class="text-secondary small">A read-only panel in the post and page editors, the report history under Fact Checking, and (after you place the "Fact Check Summary" block in a region) on the public page, with the prompt version it was checked under.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-2">
                    <i class="ti ti-history text-primary mt-1"></i>
                    <div>
                        <div class="fw-semibold">Stale means stale</div>
                        <div class="text-secondary small">Reports snapshot the content at check time. When the content is edited afterwards, the report is marked stale rather than deleted, so you know to ask for a fresh check.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Good to know</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="d-flex gap-2">
                    <i class="ti ti-shield-lock text-primary mt-1"></i>
                    <div>
                        <div class="fw-semibold">Keys are secret</div>
                        <div class="text-secondary small">They never appear again after creation, and the public pages don't share them. Anyone who has a key can do what it permits, so treat keys like passwords.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-2">
                    <i class="ti ti-power text-primary mt-1"></i>
                    <div>
                        <div class="fw-semibold">Stop them at any time</div>
                        <div class="text-secondary small">Use <strong>Disable</strong> to pause a key without deleting it, or <strong>Delete</strong> to get rid of it for good.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-2">
                    <i class="ti ti-alert-triangle text-primary mt-1"></i>
                    <div>
                        <div class="fw-semibold">Temporary blocks</div>
                        <div class="text-secondary small">If something keeps trying to use a disabled key, it is blocked for 30 minutes. Successful use clears any block automatically.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-2">
                    <i class="ti ti-file-text text-primary mt-1"></i>
                    <div>
                        <div class="fw-semibold">Everything is recorded</div>
                        <div class="text-secondary small">Every access is listed in the Audit Log on the Manage page, including who, what, and when, even failed attempts.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-2">
                    <i class="ti ti-user text-primary mt-1"></i>
                    <div>
                        <div class="fw-semibold">Content authors</div>
                        <div class="text-secondary small">Posts and pages created through the API are attributed to the <strong>default author</strong> you choose on the Manage page, so content always shows an owner.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-2">
                    <i class="ti ti-brush text-primary mt-1"></i>
                    <div>
                        <div class="fw-semibold">Safe by default</div>
                        <div class="text-secondary small">Text the assistant sends is cleaned before it's stored, so scripts or other unsafe content can't make it onto your site.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <button class="btn btn-link p-0 text-decoration-none" type="button"
                    data-bs-toggle="collapse" data-bs-target="#developerRef"
                    aria-expanded="false" aria-controls="developerRef">
                For developers <i class="ti ti-chevron-down"></i>
            </button>
        </h3>
    </div>
    <div class="collapse" id="developerRef">
        <div class="card-body border-top">
            <p class="text-secondary">
                The machine-readable endpoint reference lives in two places:
                <code>GET <?= htmlspecialchars($apiBase) ?>/help</code> serves the live guide an AI can read,
                and <code>plugins/AiAssistant/AI-README.md</code> documents
                every endpoint, the permissions each needs, and examples.
                Broken-links and analytics endpoints are stubs and return
                <code>501</code> until implemented.
            </p>
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Permission</th>
                        <th>Label</th>
                        <th>Endpoint</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($helpGroups as $group => $permissions): ?>
                        <?php foreach ($permissions as $permission => $meta): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($permission) ?></code></td>
                                <td><?= htmlspecialchars($meta['label']) ?></td>
                                <td class="text-nowrap">
                                    <?php $paths = $meta['endpoints'] ?? [$meta['path']]; ?>
                                    <?php foreach ($paths as $endpoint): ?>
                                        <span class="badge bg-secondary-lt"><?= htmlspecialchars($meta['method']) ?></span>
                                        <code><?= htmlspecialchars($endpoint) ?></code>
                                        <br>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end mt-3">
    <a href="<?= $adminBase ?>/manage" class="btn btn-primary">
        <i class="ti ti-arrow-left me-1"></i> Back to Manage
    </a>
</div>