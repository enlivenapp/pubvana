<?php

namespace App\Controllers\Admin;

use App\Models\BrokenLinkModel;

class BrokenLinks extends BaseAdminController
{
    public function index(): string
    {
        $this->requirePremium();

        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }

        $model        = new BrokenLinkModel();
        $showDismissed = (bool) $this->request->getGet('dismissed');
        $rows          = $model->getResults($showDismissed);

        // Group results by source for display
        $grouped = [];
        foreach ($rows as $row) {
            $key = $row->source_type . ':' . $row->source_id;
            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'source_type'  => $row->source_type,
                    'source_id'    => (int) $row->source_id,
                    'source_title' => $row->source_title,
                    'links'        => [],
                ];
            }
            $grouped[$key]['links'][] = $row;
        }

        return $this->adminView('broken_links/index', array_merge(
            $this->baseData('Broken Links', 'broken_links'),
            [
                'grouped'       => $grouped,
                'total'         => count($rows),
                'showDismissed' => $showDismissed,
            ]
        ));
    }

    public function recheck(int $id)
    {
        $this->requirePremium();

        $model = new BrokenLinkModel();
        $row   = $model->find($id);

        if (! $row) {
            return redirect()->to('/admin/broken-links')->with('error', 'Record not found.');
        }

        $result = $this->fetchUrl($row->url);

        $model->upsert([
            'source_type'   => $row->source_type,
            'source_id'     => (int) $row->source_id,
            'source_title'  => $row->source_title,
            'url'           => $row->url,
            'http_status'   => $result['status'],
            'error_message' => $result['error'] ?? null,
        ]);

        // If it resolved OK, remove the record
        if ($model->isOk($result['status'])) {
            $model->delete($id);
            return redirect()->to('/admin/broken-links')->with('success', 'Link is now reachable — removed from results.');
        }

        $label = $result['status'] ?? 'unreachable';
        return redirect()->to('/admin/broken-links')->with('error', "Link still broken ({$label}).");
    }

    public function dismiss(int $id)
    {
        $this->requirePremium();

        $model = new BrokenLinkModel();
        $row   = $model->find($id);

        if (! $row) {
            return redirect()->to('/admin/broken-links')->with('error', 'Record not found.');
        }

        $model->update($id, ['dismissed' => 1]);

        return redirect()->to('/admin/broken-links')->with('success', 'Link dismissed.');
    }

    // -------------------------------------------------------------------------

    private function fetchUrl(string $url): array
    {
        try {
            $client   = \Config\Services::curlrequest(['timeout' => 10]);
            $response = $client->request('HEAD', $url, [
                'http_errors'     => false,
                'allow_redirects' => ['max' => 5],
                'headers'         => ['User-Agent' => 'Pubvana-LinkChecker/1.0'],
            ]);

            $status = $response->getStatusCode();

            if ($status === 405) {
                $response = $client->request('GET', $url, [
                    'http_errors'     => false,
                    'allow_redirects' => ['max' => 5],
                    'headers'         => ['User-Agent' => 'Pubvana-LinkChecker/1.0'],
                ]);
                $status = $response->getStatusCode();
            }

            return ['status' => $status, 'error' => null];

        } catch (\Throwable $e) {
            return ['status' => null, 'error' => mb_substr($e->getMessage(), 0, 200)];
        }
    }
}
