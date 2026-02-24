<?php

namespace App\Commands;

use App\Models\BrokenLinkModel;
use App\Models\PageModel;
use App\Models\PostModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CheckBrokenLinks extends BaseCommand
{
    protected $group       = 'Pubvana';
    protected $name        = 'links:check';
    protected $description = 'Scan all published posts and pages for broken external links.';
    protected $usage       = 'links:check';

    /** @var BrokenLinkModel */
    protected BrokenLinkModel $linkModel;

    /** @var \CodeIgniter\HTTP\CURLRequest */
    protected $client;

    /** Internal host used to detect internal links */
    protected string $siteHost;

    public function run(array $params): void
    {
        $this->linkModel = new BrokenLinkModel();
        $this->client    = \Config\Services::curlrequest(['timeout' => 10]);
        $this->siteHost  = strtolower(parse_url(base_url(), PHP_URL_HOST) ?? '');

        CLI::write('Pubvana Broken Link Checker', 'cyan');
        CLI::write(str_repeat('─', 60), 'dark_gray');

        $sources = $this->collectSources();

        if (empty($sources)) {
            CLI::write('No published posts or pages found.', 'yellow');
            return;
        }

        $totalLinks  = 0;
        $brokenCount = 0;

        foreach ($sources as $source) {
            $links = $this->extractLinks($source['content']);

            if (empty($links)) {
                continue;
            }

            CLI::write(sprintf(
                '[%s #%d] %s (%d link%s)',
                strtoupper($source['type']),
                $source['id'],
                mb_substr($source['title'], 0, 50),
                count($links),
                count($links) !== 1 ? 's' : ''
            ), 'white');

            foreach ($links as $url) {
                $totalLinks++;
                $result = $this->checkUrl($url);

                $isBroken = ! $this->linkModel->isOk($result['status']);

                $this->linkModel->upsert([
                    'source_type'   => $source['type'],
                    'source_id'     => $source['id'],
                    'source_title'  => $source['title'],
                    'url'           => $url,
                    'http_status'   => $result['status'],
                    'error_message' => $result['error'] ?? null,
                ]);

                if ($isBroken) {
                    $brokenCount++;
                    $label  = $result['status'] ? (string) $result['status'] : 'ERR';
                    CLI::write(sprintf('  [%s] %s', $label, $url), 'red');
                    if (! empty($result['error'])) {
                        CLI::write('       ' . $result['error'], 'dark_gray');
                    }
                } else {
                    CLI::write(sprintf('  [%d] %s', $result['status'], $url), 'green');
                }
            }
        }

        // Remove any rows that are now OK (links that were fixed since last scan)
        foreach ($sources as $source) {
            $this->linkModel->deleteOk($source['type'], $source['id']);
        }

        CLI::write(str_repeat('─', 60), 'dark_gray');
        CLI::write(sprintf(
            'Checked %d link%s across %d source%s. %d broken.',
            $totalLinks,  $totalLinks  !== 1 ? 's' : '',
            count($sources), count($sources) !== 1 ? 's' : '',
            $brokenCount
        ), $brokenCount > 0 ? 'yellow' : 'green');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Collect all published posts and pages as flat source arrays.
     *
     * @return array<array{type:string, id:int, title:string, content:string}>
     */
    private function collectSources(): array
    {
        $sources = [];

        $posts = (new PostModel())->published()
            ->select('id, title, content')
            ->findAll();

        foreach ($posts as $post) {
            $sources[] = [
                'type'    => 'post',
                'id'      => (int) $post->id,
                'title'   => $post->title,
                'content' => (string) ($post->content ?? ''),
            ];
        }

        $pages = (new PageModel())->published()
            ->select('id, title, content')
            ->findAll();

        foreach ($pages as $page) {
            $sources[] = [
                'type'    => 'page',
                'id'      => (int) $page->id,
                'title'   => $page->title,
                'content' => (string) ($page->content ?? ''),
            ];
        }

        return $sources;
    }

    /**
     * Extract all unique, checkable external href values from HTML content.
     *
     * @return string[]
     */
    private function extractLinks(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        $doc = new \DOMDocument();
        // Suppress warnings from malformed HTML
        @$doc->loadHTML('<meta charset="utf-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);

        $urls = [];
        foreach ($doc->getElementsByTagName('a') as $node) {
            $href = trim($node->getAttribute('href'));

            if ($href === '') continue;

            // Skip anchors, mailto:, tel:, javascript:, data:
            if (preg_match('/^(#|mailto:|tel:|javascript:|data:)/i', $href)) continue;

            // Must be an absolute URL
            if (! preg_match('/^https?:\/\//i', $href)) continue;

            // Skip internal links
            $host = strtolower(parse_url($href, PHP_URL_HOST) ?? '');
            if ($host === $this->siteHost) continue;

            $urls[$href] = true; // deduplicate within this source
        }

        return array_keys($urls);
    }

    /**
     * Send a HEAD request (with GET fallback on 405) and return status + error.
     *
     * @return array{status: int|null, error: string|null}
     */
    private function checkUrl(string $url): array
    {
        try {
            $response = $this->client->request('HEAD', $url, [
                'http_errors'     => false,
                'allow_redirects' => ['max' => 5],
                'headers'         => [
                    'User-Agent' => 'Pubvana-LinkChecker/1.0',
                ],
            ]);

            $status = $response->getStatusCode();

            // Some servers don't support HEAD — fall back to GET
            if ($status === 405) {
                $response = $this->client->request('GET', $url, [
                    'http_errors'     => false,
                    'allow_redirects' => ['max' => 5],
                    'headers'         => [
                        'User-Agent' => 'Pubvana-LinkChecker/1.0',
                    ],
                ]);
                $status = $response->getStatusCode();
            }

            return ['status' => $status, 'error' => null];

        } catch (\Throwable $e) {
            return ['status' => null, 'error' => mb_substr($e->getMessage(), 0, 200)];
        }
    }
}
