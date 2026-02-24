<?php

namespace App\Services;

class UpdateService
{
    protected string $apiUrl   = 'https://api.github.com/repos/enlivenapp/pubvana/releases/latest';
    protected string $cacheKey = 'pubvana_update_check';
    protected int    $cacheTtl = 21600; // 6 hours

    /**
     * Check GitHub for the latest release.
     *
     * Returns an array with keys:
     *   available        bool
     *   current_version  string
     *   latest_version   string
     *   release_url      string
     *   release_notes    string
     *   zipball_url      string
     *   error            string|null
     */
    public function checkForUpdate(): array
    {
        $base = [
            'available'       => false,
            'current_version' => APP_VERSION,
            'latest_version'  => APP_VERSION,
            'release_url'     => '',
            'release_notes'   => '',
            'zipball_url'     => '',
            'error'           => null,
        ];

        $cached = cache($this->cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $client   = \Config\Services::curlrequest(['timeout' => 5]);
            $response = $client->get($this->apiUrl, [
                'http_errors' => false,
                'headers'     => [
                    'User-Agent' => 'Pubvana-CMS/' . APP_VERSION,
                    'Accept'     => 'application/vnd.github.v3+json',
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                $base['error'] = 'GitHub returned HTTP ' . $response->getStatusCode();
                return $base;
            }

            $data = json_decode($response->getBody(), true);
            if (! is_array($data) || empty($data['tag_name'])) {
                $base['error'] = 'Unexpected response from GitHub.';
                return $base;
            }

            $latest = ltrim($data['tag_name'], 'v');

            $result = [
                'available'       => version_compare($latest, APP_VERSION, '>'),
                'current_version' => APP_VERSION,
                'latest_version'  => $latest,
                'release_url'     => $data['html_url']    ?? '',
                'release_notes'   => $data['body']        ?? '',
                'zipball_url'     => $data['zipball_url'] ?? '',
                'error'           => null,
            ];

            cache()->save($this->cacheKey, $result, $this->cacheTtl);

            return $result;
        } catch (\Throwable $e) {
            log_message('warning', 'UpdateService: ' . $e->getMessage());
            $base['error'] = $e->getMessage();
            return $base;
        }
    }

    public function clearCache(): void
    {
        cache()->delete($this->cacheKey);
    }
}
