<?php

namespace App\Services;

class PremiumService
{
    protected string $validateUrl = 'https://pubvana.net/api/license/validate';
    protected int    $revalidateDays = 90;

    /**
     * Returns true if the site has a valid Premium Core licence,
     * or if running on a local dev domain (always-on bypass).
     */
    public function isLicensed(): bool
    {
        if ($this->isDev()) {
            return true;
        }

        $this->revalidateIfDue();

        return (int) setting('Premium.licenseValid') === 1;
    }

    /**
     * Human-readable status string.
     * Possible values: 'dev' | 'valid' | 'invalid' | 'unchecked' | 'unreachable'
     */
    public function status(): string
    {
        if ($this->isDev()) {
            return 'dev';
        }

        $valid   = setting('Premium.licenseValid');
        $key     = setting('Premium.licenseKey');

        if ($key === null || $key === '') {
            return 'unchecked';
        }

        if ($valid === null) {
            return 'unreachable';
        }

        return (int) $valid === 1 ? 'valid' : 'invalid';
    }

    /**
     * Store a new licence key and immediately validate it against the API.
     * Returns ['valid' => bool, 'error' => string|null].
     */
    public function setKey(string $key): array
    {
        $key = trim($key);
        setting()->set('Premium.licenseKey', $key);

        if ($key === '') {
            setting()->set('Premium.licenseValid', null);
            setting()->set('Premium.licenseLastChecked', null);
            return ['valid' => false, 'error' => 'No licence key provided.'];
        }

        return $this->validateWithApi($key);
    }

    /**
     * Re-validates the stored key if more than $revalidateDays have elapsed.
     */
    public function revalidateIfDue(): void
    {
        $key         = setting('Premium.licenseKey');
        $lastChecked = setting('Premium.licenseLastChecked');

        if (! $key) {
            return;
        }

        $due = $lastChecked === null
            || (time() - (int) $lastChecked) > ($this->revalidateDays * 86400);

        if ($due) {
            $this->validateWithApi($key);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function isDev(): bool
    {
        $host = strtolower(parse_url(base_url(), PHP_URL_HOST) ?? '');
        return $host === 'localhost'
            || str_starts_with($host, '127.')
            || str_ends_with($host, '.local');
    }

    private function validateWithApi(string $key): array
    {
        $domain = parse_url(base_url(), PHP_URL_HOST) ?? '';

        try {
            $client = \Config\Services::curlrequest(['timeout' => 8]);
            $response = $client->post($this->validateUrl, [
                'form_params' => [
                    'license_key' => $key,
                    'domain'      => $domain,
                    'item_slug'   => 'premium',
                ],
                'http_errors' => false,
            ]);

            $body = json_decode($response->getBody(), true);
            $valid = isset($body['valid']) && $body['valid'] ? 1 : 0;
            $error = $body['error'] ?? null;

            setting()->set('Premium.licenseValid', $valid);
            setting()->set('Premium.licenseLastChecked', time());

            return ['valid' => (bool) $valid, 'error' => $error];

        } catch (\Throwable $e) {
            log_message('warning', 'PremiumService: API unreachable — ' . $e->getMessage());
            // Fail-open: leave existing licenseValid unchanged; record attempt time
            setting()->set('Premium.licenseLastChecked', time());
            setting()->set('Premium.licenseValid', null);
            return ['valid' => false, 'error' => 'Could not reach the licence server. Try again later.'];
        }
    }
}
