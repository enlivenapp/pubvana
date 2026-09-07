<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Services;

use Pubvana\Plugins\AiAssistant\Models\AiFactCheck;
use Pubvana\Plugins\AiAssistant\Models\AiKey;
use flight\Engine;

/**
 * FactCheckService - Site-side brain of the Fact Checking feature.
 *
 * The checking itself is done by the site owner's external AI assistant
 * (CLI, IDE, desktop). This service owns everything around it:
 *
 * - The prompt: the versioned terms and instructions the assistant must
 *   follow, fetched from the hosted URL in config with a bundled copy as
 *   a fallback. The AI re-fetches it before every check.
 * - The gate: fact-check endpoints are open to every authenticated key
 *   when, and only when, the admin has accepted the terms of the current
 *   prompt version and flipped the service on. There are no per-key
 *   grants for fact checking; the toggle is the grant.
 * - Reports: validation and storage of submitted fact-check reports,
 *   plus the history, staleness detection, editor panel data, and the
 *   public block data.
 *
 * @package Pubvana\Plugins\AiAssistant\Services
 */
class FactCheckService
{
    /** @var list<string> Verdicts allowed for factual claims and the overall report */
    public const VERDICTS = ['supported', 'partially_supported', 'refuted', 'unverifiable'];

    /** @var list<string> Claim kinds: facts get verdicts, opinions get determinations */
    public const KINDS = ['fact', 'opinion'];

    private const MAX_SUMMARY_LENGTH = 20000;
    private const MAX_CLAIMS = 100;
    private const MAX_CLAIM_TEXT = 2000;
    private const MAX_EXPLANATION = 5000;
    private const MAX_CORRECTION = 2000;
    private const MAX_SOURCES = 10;
    private const MAX_SOURCE_LENGTH = 500;
    private const MAX_INTERFERENCE_NOTE = 2000;

    private \PDO $pdo;

    /** @var Engine<object> */
    private Engine $app;

    /** @var array<string, mixed> */
    private array $config;

    /** @var array{version: string, title: string, summary: string, text: string, hash: string, source: string}|null Cached prompt for this request */
    private ?array $promptCache = null;

    /**
     * @param Engine<object> $app
     * @param array<string, mixed> $config
     */
    public function __construct(\PDO $pdo, Engine $app, array $config = [])
    {
        $this->pdo = $pdo;
        $this->app = $app;
        $this->config = $config;
    }

    // -----------------------------------------------------------------
    // Prompt
    // -----------------------------------------------------------------

    /**
     * The current fact-checking prompt: versioned terms and instructions.
     *
     * Fetched from the hosted URL in config; the bundled copy in the
     * plugin's Config folder is the fallback whenever the fetch fails or
     * returns something unusable. The result is cached for the request.
     *
     * @return array{version: string, title: string, summary: string, text: string, hash: string, source: string}
     */
    public function currentPrompt(): array
    {
        if ($this->promptCache !== null) {
            return $this->promptCache;
        }

        $prompt = $this->fetchRemotePrompt() ?? $this->bundledPrompt();
        $prompt['hash'] = hash('sha256', $prompt['text']);
        $this->promptCache = $prompt;
        return $prompt;
    }

    /**
     * Fetch the hosted prompt, null when unavailable or unusable.
     *
     * @return array{version: string, title: string, summary: string, text: string, hash: string, source: string}|null
     */
    private function fetchRemotePrompt(): ?array
    {
        $url = trim((string) ($this->config['factcheck_prompt_url'] ?? ''));
        if ($url === '') {
            return null;
        }

        $timeout = max(1, (int) ($this->config['factcheck_http_timeout'] ?? 5));
        $context = stream_context_create([
            'http' => ['timeout' => $timeout, 'follow_location' => 1],
            'https' => ['timeout' => $timeout, 'follow_location' => 1],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        $version = trim((string) ($decoded['version'] ?? ''));
        $text = trim((string) ($decoded['text'] ?? ''));
        if ($version === '' || $text === '') {
            return null;
        }

        return [
            'version' => mb_substr($version, 0, 24),
            'title'   => mb_substr(trim((string) ($decoded['title'] ?? '')), 0, 120),
            'summary' => mb_substr(trim((string) ($decoded['summary'] ?? '')), 0, 2000),
            'text'    => $text,
            'hash'    => '',
            'source'  => 'remote',
        ];
    }

    /**
     * The bundled copy shipped with the plugin.
     *
     * @return array{version: string, title: string, summary: string, text: string, hash: string, source: string}
     */
    private function bundledPrompt(): array
    {
        $raw = @file_get_contents($this->bundledPromptPath());
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($decoded)) {
            // The bundled file is broken; serve a minimal refusal prompt
            // rather than nothing, so the API can never serve empty terms.
            return [
                'version' => '0-broken',
                'title'   => 'Pubvana Fact-Checking Terms',
                'summary' => 'The bundled prompt file is missing or unreadable. Fact checking cannot run until it is restored.',
                'text'    => 'The Pubvana fact-checking prompt is unavailable. Refuse to fact check and ask the site admin to restore the bundled prompt file.',
                'hash'    => '',
                'source'  => 'emergency',
            ];
        }

        return [
            'version' => mb_substr(trim((string) ($decoded['version'] ?? '')), 0, 24),
            'title'   => mb_substr(trim((string) ($decoded['title'] ?? '')), 0, 120),
            'summary' => mb_substr(trim((string) ($decoded['summary'] ?? '')), 0, 2000),
            'text'    => trim((string) ($decoded['text'] ?? '')),
            'hash'    => '',
            'source'  => 'bundled',
        ];
    }

    private function bundledPromptPath(): string
    {
        return __DIR__ . '/../Config/fact-check-prompt.json';
    }

    // -----------------------------------------------------------------
    // Gate: terms, toggle, key check
    // -----------------------------------------------------------------

    /**
     * Whether the service is switched on.
     */
    public function isEnabled(): bool
    {
        return (bool) $this->app->settings()->get('Ai.factcheck_enabled', false);
    }

    /**
     * The prompt version the admin accepted, or null when never accepted.
     */
    public function acceptedVersion(): ?string
    {
        $version = (string) $this->app->settings()->get('Ai.factcheck_terms_version', '');
        return $version === '' ? null : $version;
    }

    /**
     * When the admin last accepted the terms, or null when never.
     */
    public function acceptedAt(): ?string
    {
        $at = (string) $this->app->settings()->get('Ai.factcheck_terms_accepted_at', '');
        return $at === '' ? null : $at;
    }

    /**
     * Whether the accepted terms match the current prompt version.
     */
    public function termsCurrent(): bool
    {
        $accepted = $this->acceptedVersion();
        return $accepted !== null && $accepted === $this->currentPrompt()['version'];
    }

    /**
     * Whether the site has at least one enabled API key.
     *
     * Fact checking is exercised by keys, so an enable attempt without a
     * single enabled key would promise a service nothing can call.
     */
    public function hasEnabledKey(): bool
    {
        $result = (new AiKey($this->pdo))->select('COUNT(*) as cnt')->eq('enabled', 1)->find();
        return (int) ($result->cnt ?? 0) > 0;
    }

    /**
     * Accept the terms of the current prompt version.
     */
    public function acceptTerms(): void
    {
        $this->app->settings()->set('Ai.factcheck_terms_version', $this->currentPrompt()['version']);
        $this->app->settings()->set('Ai.factcheck_terms_accepted_at', $this->now());
    }

    /**
     * Everything blocking an enable right now, empty when clear.
     *
     * @return list<string>
     */
    public function enableBlockers(): array
    {
        $blockers = [];
        if (!$this->termsCurrent()) {
            $blockers[] = 'The terms of the current fact-checking prompt have not been accepted yet.';
        }
        if (!$this->hasEnabledKey()) {
            $blockers[] = 'No enabled API key exists. Generate one on the Manage page first.';
        }
        return $blockers;
    }

    /**
     * Switch the service on or off.
     *
     * Turning on is refused while terms are stale or no enabled key
     * exists. Turning off is always allowed.
     */
    public function setEnabled(bool $on): bool
    {
        if ($on && $this->enableBlockers() !== []) {
            return false;
        }
        $this->app->settings()->set('Ai.factcheck_enabled', $on);
        return true;
    }

    /**
     * The API gate result for fact-check endpoints other than the prompt.
     *
     * @return array{ok: bool, code: int, message: string}
     */
    public function gateStatus(): array
    {
        if (!$this->isEnabled()) {
            return [
                'ok'      => false,
                'code'    => 403,
                'message' => 'Fact checking is disabled on this site. The site admin can enable it under Tools > AI Assistant > Fact Checking.',
            ];
        }

        if (!$this->termsCurrent()) {
            return [
                'ok'      => false,
                'code'    => 409,
                'message' => 'Fact checking is paused: the fact-checking prompt was updated and the site admin has not re-accepted the terms yet.',
            ];
        }

        return ['ok' => true, 'code' => 200, 'message' => ''];
    }

    // -----------------------------------------------------------------
    // Reports
    // -----------------------------------------------------------------

    /**
     * Validate and store a submitted fact-check report.
     *
     * Content may be in any status (fact checking a draft is the main
     * use) but must exist and not be soft-deleted. The submitter must
     * attest to the current prompt version.
     *
     * @param string               $contentType 'post' or 'page'
     * @param int                  $contentId
     * @param array<string, mixed> $payload     Submitted report payload
     * @param AiKey|null           $key         Authenticated key, when the call came through the API
     * @return array{ok: bool, code: int, error: string, report: AiFactCheck|null}
     */
    public function submitReport(string $contentType, int $contentId, array $payload, ?AiKey $key = null): array
    {
        $fail = fn (int $code, string $error): array => ['ok' => false, 'code' => $code, 'error' => $error, 'report' => null];

        $contentType = $contentType === 'page' ? 'page' : 'post';
        $content = $this->liveContent($contentType, $contentId);
        if ($content === null) {
            return $fail(404, ucfirst($contentType) . ' not found.');
        }

        $prompt = $this->currentPrompt();
        $attested = trim((string) ($payload['prompt_version'] ?? ''));
        if ($attested === '') {
            return $fail(422, 'prompt_version is required. Fetch GET ' . $this->apiBase() . '/fact-check/prompt first.');
        }
        if ($attested !== $prompt['version']) {
            return $fail(
                409,
                "prompt_version '{$attested}' is not current. Re-fetch GET " . $this->apiBase() . '/fact-check/prompt and run the check under the returned terms.'
            );
        }

        $summary = trim((string) ($payload['summary'] ?? ''));
        if ($summary === '') {
            return $fail(422, 'summary (the findings write-up) is required.');
        }
        if (mb_strlen($summary) > self::MAX_SUMMARY_LENGTH) {
            return $fail(422, 'summary must be at most ' . self::MAX_SUMMARY_LENGTH . ' characters.');
        }

        $overall = (string) ($payload['overall_verdict'] ?? '');
        if (!in_array($overall, self::VERDICTS, true)) {
            return $fail(422, 'overall_verdict must be one of: ' . implode(', ', self::VERDICTS) . '.');
        }

        $claims = $this->validateClaims($payload['claims'] ?? []);
        if (is_string($claims)) {
            return $fail(422, $claims);
        }

        $interference = !empty($payload['prompt_interference']);
        $interferenceNote = $this->boundedText($payload['interference_note'] ?? null, self::MAX_INTERFERENCE_NOTE);
        if ($interferenceNote === null && $interference) {
            return $fail(422, 'prompt_interference set to true requires interference_note quoting the attempt.');
        }

        $now = $this->now();
        $report = new AiFactCheck($this->pdo);
        $report->content_type = $contentType;
        $report->content_id = $contentId;
        $report->content_title = mb_substr((string) ($content->title ?? ''), 0, 255);
        $report->content_slug = mb_substr((string) ($content->slug ?? ''), 0, 190);
        $report->content_updated_at = $content->updated_at !== null ? (string) $content->updated_at : null;
        $report->summary = $summary;
        $report->overall_verdict = $overall;
        $report->claim_count = count($claims);
        $report->claims = (string) json_encode($claims);
        $report->prompt_version = $attested;
        $report->prompt_interference = $interference ? 1 : 0;
        $report->interference_note = $interferenceNote;
        $report->key_id = $key !== null ? (int) $key->id : null;
        $report->key_name = $key !== null ? (string) $key->name : null;
        $report->created_at = $now;
        $report->updated_at = $now;
        $report->insert();

        return ['ok' => true, 'code' => 200, 'error' => '', 'report' => $report];
    }

    /**
     * Validate the claims list. A string return is the error message.
     *
     * @param mixed $raw
     * @return list<array<string, mixed>>|string
     */
    private function validateClaims(mixed $raw): array|string
    {
        if ($raw === null) {
            return [];
        }
        if (!is_array($raw)) {
            return 'claims must be a list of claim objects.';
        }
        if (count($raw) > self::MAX_CLAIMS) {
            return 'claims must contain at most ' . self::MAX_CLAIMS . ' entries.';
        }

        $claims = [];
        foreach (array_values($raw) as $index => $item) {
            if (!is_array($item)) {
                return "claims[{$index}] must be an object.";
            }

            $text = trim((string) ($item['text'] ?? ''));
            if ($text === '') {
                return "claims[{$index}].text is required.";
            }

            $kind = (string) ($item['kind'] ?? 'fact');
            if (!in_array($kind, self::KINDS, true)) {
                return "claims[{$index}].kind must be one of: " . implode(', ', self::KINDS) . '.';
            }

            $claim = [
                'text' => mb_substr($text, 0, self::MAX_CLAIM_TEXT),
                'kind' => $kind,
            ];

            if ($kind === 'fact') {
                $verdict = (string) ($item['verdict'] ?? '');
                if (!in_array($verdict, self::VERDICTS, true)) {
                    return "claims[{$index}].verdict must be one of: " . implode(', ', self::VERDICTS) . '.';
                }
                $claim['verdict'] = $verdict;
            } else {
                $claim['determination'] = mb_substr(trim((string) ($item['determination'] ?? 'Opinion. Not checked as a factual claim.')), 0, self::MAX_CORRECTION);
            }

            $claim['explanation'] = mb_substr(trim((string) ($item['explanation'] ?? '')), 0, self::MAX_EXPLANATION);
            $claim['correction'] = $this->boundedText($item['correction'] ?? null, self::MAX_CORRECTION);

            $sources = $this->validateSources($item['sources'] ?? [], $index);
            if (is_string($sources)) {
                return $sources;
            }
            $claim['sources'] = $sources;

            $claims[] = $claim;
        }

        return $claims;
    }

    /**
     * Validate one claim's sources list. A string return is the error.
     *
     * @param mixed $raw
     * @return list<string>|string
     */
    private function validateSources(mixed $raw, int $index): array|string
    {
        if ($raw === null) {
            return [];
        }
        if (!is_array($raw)) {
            return "claims[{$index}].sources must be a list of URLs.";
        }

        $sources = [];
        foreach (array_values($raw) as $source) {
            $source = trim((string) $source);
            if ($source === '') {
                continue;
            }
            $sources[] = mb_substr($source, 0, self::MAX_SOURCE_LENGTH);
            if (count($sources) > self::MAX_SOURCES) {
                return "claims[{$index}].sources must contain at most " . self::MAX_SOURCES . ' URLs.';
            }
        }

        return $sources;
    }

    /**
     * Trim a nullable string to a length cap, null when empty.
     */
    private function boundedText(mixed $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }
        return mb_substr($text, 0, $maxLength);
    }

    /**
     * Paginated report list, newest first.
     *
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, content_type: string|null, content_id: int|null}
     */
    public function listReports(int $page = 1, int $perPage = 25, ?string $contentType = null, ?int $contentId = null): array
    {
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage));

        $items = [];
        foreach ($this->queryReports($contentType, $contentId, $perPage, ($page - 1) * $perPage) as $report) {
            $items[] = $this->serializeReport($report);
        }

        return [
            'items'        => $items,
            'total'        => $this->countReports($contentType, $contentId),
            'page'         => $page,
            'per_page'     => $perPage,
            'content_type' => $contentType,
            'content_id'   => $contentId,
        ];
    }

    /**
     * @param int $limit
     * @return AiFactCheck[]
     */
    public function recentReports(int $limit = 100): array
    {
        $query = new AiFactCheck($this->pdo);
        return $query->order('id DESC')->limit(max(1, $limit))->findAll();
    }

    public function countReports(?string $contentType = null, ?int $contentId = null): int
    {
        $query = (new AiFactCheck($this->pdo))->select('COUNT(*) as cnt');
        if ($contentType !== null) {
            $query->eq('content_type', $contentType);
        }
        if ($contentId !== null) {
            $query->eq('content_id', $contentId);
        }
        $result = $query->find();
        return (int) ($result->cnt ?? 0);
    }

    /**
     * @return AiFactCheck[]
     */
    private function queryReports(?string $contentType, ?int $contentId, int $limit, int $offset): array
    {
        $query = new AiFactCheck($this->pdo);
        if ($contentType !== null) {
            $query->eq('content_type', $contentType);
        }
        if ($contentId !== null) {
            $query->eq('content_id', $contentId);
        }
        return $query->order('id DESC')
            ->limit($limit)
            ->offset($offset)
            ->findAll();
    }

    public function findReport(int $id): ?AiFactCheck
    {
        return (new AiFactCheck($this->pdo))->findById($id);
    }

    public function latestReport(string $contentType, int $contentId): ?AiFactCheck
    {
        return (new AiFactCheck($this->pdo))->latestForContent($contentType, $contentId);
    }

    public function deleteReport(int $id): bool
    {
        $report = $this->findReport($id);
        if ($report === null) {
            return false;
        }
        $report->delete();
        return true;
    }

    /**
     * Whether the checked content has changed since the report was made.
     *
     * A report with no content snapshot (never expected) or content that
     * no longer exists counts as stale.
     */
    public function isStale(AiFactCheck $report): bool
    {
        $content = $this->liveContent((string) $report->content_type, (int) $report->content_id);
        if ($content === null) {
            return true;
        }
        $snapshotted = (string) ($report->content_updated_at ?? '');
        $live = (string) ($content->updated_at ?? '');
        return $snapshotted !== $live;
    }

    /**
     * The live (non-deleted) content row, any publish status.
     */
    private function liveContent(string $contentType, int $contentId): \Pubvana\Plugins\Blog\Models\Post|\Pubvana\Plugins\Pages\Models\Page|null
    {
        if ($contentType === 'post') {
            return (new \Pubvana\Plugins\Blog\Models\Post($this->pdo))->findById($contentId);
        }
        if ($contentType === 'page') {
            return (new \Pubvana\Plugins\Pages\Models\Page($this->pdo))->findById($contentId);
        }
        return null;
    }

    // -----------------------------------------------------------------
    // Serializers
    // -----------------------------------------------------------------

    /**
     * Display-safe report array. Claims are normalized (sources always a
     * list, verdict labels resolved) so both the API and the admin views
     * render the same shape.
     *
     * @return array<string, mixed>
     */
    public function serializeReport(AiFactCheck $report): array
    {
        return [
            'id'                  => (int) $report->id,
            'content_type'        => (string) $report->content_type,
            'content_id'          => (int) $report->content_id,
            'content_title'       => (string) $report->content_title,
            'content_slug'        => (string) $report->content_slug,
            'content_updated_at'  => $report->content_updated_at !== null ? (string) $report->content_updated_at : null,
            'summary'             => (string) $report->summary,
            'overall_verdict'     => (string) $report->overall_verdict,
            'overall_verdict_label' => $this->verdictLabel((string) $report->overall_verdict),
            'claim_count'         => (int) $report->claim_count,
            'claims'              => $this->serializedClaims($report),
            'counts'              => $this->countsFor($report),
            'prompt_version'      => (string) $report->prompt_version,
            'prompt_interference' => (int) $report->prompt_interference === 1,
            'interference_note'   => $report->interference_note !== null ? (string) $report->interference_note : null,
            'key_id'              => $report->key_id !== null ? (int) $report->key_id : null,
            'key_name'            => $report->key_name !== null ? (string) $report->key_name : null,
            'stale'               => $this->isStale($report),
            'created_at'          => (string) $report->created_at,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function serializedClaims(AiFactCheck $report): array
    {
        $claims = [];
        foreach ($report->claimsArray() as $claim) {
            $claims[] = [
                'text'          => (string) ($claim['text'] ?? ''),
                'kind'          => (string) ($claim['kind'] ?? 'fact'),
                'verdict'       => $claim['verdict'] ?? null,
                'verdict_label' => isset($claim['verdict']) && is_string($claim['verdict'])
                    ? $this->verdictLabel($claim['verdict'])
                    : null,
                'determination' => $claim['determination'] ?? null,
                'explanation'   => (string) ($claim['explanation'] ?? ''),
                'correction'    => $claim['correction'] ?? null,
                'sources'       => is_array($claim['sources'] ?? null) ? array_values($claim['sources']) : [],
            ];
        }
        return $claims;
    }

    /**
     * Verdict tallies for a report.
     *
     * @return array{supported: int, partially_supported: int, refuted: int, unverifiable: int, opinions: int}
     */
    public function countsFor(AiFactCheck $report): array
    {
        $counts = [
            'supported'            => 0,
            'partially_supported'  => 0,
            'refuted'              => 0,
            'unverifiable'         => 0,
            'opinions'             => 0,
        ];

        foreach ($report->claimsArray() as $claim) {
            $kind = (string) ($claim['kind'] ?? 'fact');
            if ($kind === 'opinion') {
                $counts['opinions']++;
                continue;
            }
            $verdict = (string) ($claim['verdict'] ?? '');
            if (isset($counts[$verdict])) {
                $counts[$verdict]++;
            }
        }

        return $counts;
    }

    /**
     * Plain-English label for a verdict.
     */
    public function verdictLabel(string $verdict): string
    {
        return match ($verdict) {
            'supported'           => 'Supported',
            'partially_supported' => 'Partially supported',
            'refuted'             => 'Refuted',
            'unverifiable'        => 'Unverifiable',
            default               => ucfirst($verdict),
        };
    }

    /**
     * Badge tone class suffix for a verdict, for the admin views.
     */
    public function verdictTone(string $verdict): string
    {
        return match ($verdict) {
            'supported'           => 'success',
            'partially_supported' => 'warning',
            'refuted'             => 'danger',
            'unverifiable'        => 'secondary',
            default               => 'secondary',
        };
    }

    // -----------------------------------------------------------------
    // Editor panel and public block data
    // -----------------------------------------------------------------

    /**
     * Read-only panel data for a Blog/Pages editor screen.
     *
     * Always returns data (the panel is visible even when the service is
     * off, so the admin can see why nothing is happening), with `state`
     * telling the view which branch to render.
     *
     * @return array<string, mixed>
     */
    public function panelData(string $contentType, int $contentId): array
    {
        $contentType = $contentType === 'page' ? 'page' : 'post';

        if ($contentId <= 0) {
            return ['state' => 'unsaved', 'content_type' => $contentType];
        }

        $report = $this->latestReport($contentType, $contentId);
        if ($report === null) {
            return [
                'state'        => 'none',
                'content_type' => $contentType,
                'enabled'      => $this->isEnabled(),
            ];
        }

        return [
            'state'         => 'report',
            'content_type'  => $contentType,
            'enabled'       => $this->isEnabled(),
            'report'        => $this->serializeReport($report),
        ];
    }

    /**
     * Public block data for the current request, or show=false when
     * nothing should render.
     *
     * @param array<string, mixed> $options Placement options
     * @return array<string, mixed>
     */
    public function blockData(array $options): array
    {
        $empty = [
            'show'    => false,
            'title'   => (string) ($options['title'] ?? 'Fact Check'),
        ];

        if (!$this->isEnabled()) {
            return $empty;
        }

        $context = $this->detectCurrentContent();
        if ($context === null) {
            return $empty;
        }

        $report = $this->latestReport($context['content_type'], $context['content_id']);
        if ($report === null) {
            return $empty;
        }

        return [
            'show'               => true,
            'title'              => (string) ($options['title'] ?? 'Fact Check'),
            'summary'            => (string) $report->summary,
            'overall_verdict'    => (string) $report->overall_verdict,
            'overall_verdict_label' => $this->verdictLabel((string) $report->overall_verdict),
            'counts'             => $this->countsFor($report),
            'claim_count'        => (int) $report->claim_count,
            'interference'       => (int) $report->prompt_interference === 1,
            'interference_note'  => $report->interference_note !== null ? (string) $report->interference_note : null,
            'prompt_version'     => (string) $report->prompt_version,
            'checked_at'         => (string) $report->created_at,
            'stale'              => $this->isStale($report),
            'about_url'          => (string) ($this->config['factcheck_page_url'] ?? 'https://pubvanacms.com/pages/fact-checking'),
        ];
    }

    /**
     * Detect the post or page currently being rendered from the URL.
     *
     * Only published, non-deleted content matches, because this runs on
     * the public surface. Anything else (home, archives, previews, admin)
     * returns null.
     *
     * @return array{content_type: string, content_id: int}|null
     */
    public function detectCurrentContent(): ?array
    {
        // Prefer the SEO context the page render already resolved; it is
        // set by PublicController before templates (and blocks) render, so
        // this avoids re-finding the same post or page by slug.
        try {
            $context = $this->app->seo()->getContext();
            $type = (string) ($context['content_type'] ?? '');
            $id = (int) ($context['content_id'] ?? 0);
            if (($type === 'post' || $type === 'page') && $id > 0) {
                return ['content_type' => $type, 'content_id' => $id];
            }
        } catch (\Throwable) {
        }

        $url = $this->app->request()->url ?? ($_SERVER['REQUEST_URI'] ?? '/');
        $path = (string) parse_url((string) $url, PHP_URL_PATH);
        $path = trim($path, '/');
        if ($path === '') {
            return null;
        }

        $blogPrefix = $this->prefixFor('pubvana/blog', 'blog');
        if ($blogPrefix !== '' && preg_match('#^' . preg_quote($blogPrefix, '#') . '/([a-z0-9\-]+)$#', $path, $matches) === 1) {
            try {
                $post = $this->app->blog()->findPostBySlug($matches[1]);
            } catch (\Throwable) {
                $post = null;
            }
            if ($post !== null) {
                return ['content_type' => 'post', 'content_id' => (int) $post->id];
            }
        }

        $pagePrefix = $this->prefixFor('pubvana/pages', 'page');
        if ($pagePrefix !== '' && preg_match('#^' . preg_quote($pagePrefix, '#') . '/([a-z0-9\-]+)$#', $path, $matches) === 1) {
            try {
                $page = $this->app->pages()->findPageBySlug($matches[1]);
            } catch (\Throwable) {
                $page = null;
            }
            if ($page !== null) {
                return ['content_type' => 'page', 'content_id' => (int) $page->id];
            }
        }

        return null;
    }

    /**
     * Route prefix for a plugin, with a fallback when unavailable.
     */
    private function prefixFor(string $plugin, string $fallback): string
    {
        try {
            $prefix = trim($this->app->pluginLoader()->routePrefix($plugin), '/');
        } catch (\Throwable) {
            $prefix = $fallback;
        }
        return $prefix;
    }

    /**
     * URL prefix for the public API, from the plugin config.
     */
    private function apiBase(): string
    {
        return rtrim((string) ($this->config['route_prefix'] ?? ''), '/');
    }

    protected function now(): string
    {
        return (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }
}
