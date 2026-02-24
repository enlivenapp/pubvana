<?php

namespace App\Controllers\Admin;

use App\Models\PageViewModel;

class Analytics extends BaseAdminController
{
    public function index(): string
    {
        $this->requirePremium();

        $days  = $this->validDays($this->request->getGet('days'));
        $model = new PageViewModel();

        return $this->adminView('analytics/index', array_merge(
            $this->baseData('Analytics', 'analytics'),
            $this->buildData($model, $days)
        ));
    }

    public function data()
    {
        $this->requirePremium();

        $days  = $this->validDays($this->request->getGet('days'));
        $model = new PageViewModel();

        return $this->response->setJSON($this->buildData($model, $days));
    }

    // -------------------------------------------------------------------------

    private function buildData(PageViewModel $model, int $days): array
    {
        return [
            'days'       => $days,
            'totalViews' => $model->totalViews($days),
            'topPosts'   => $model->getTopPosts($days, 10),
            'referrers'  => $model->getReferrers($days, 10),
            'chart'      => $this->buildChart($model->getViewsByDay($days), $days),
        ];
    }

    /**
     * Fill in every day in the period so the chart has no gaps.
     */
    private function buildChart(array $rows, int $days): array
    {
        $lookup = [];
        foreach ($rows as $row) {
            $lookup[$row->date] = (int) $row->view_count;
        }

        $labels = [];
        $values = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date     = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('M j', strtotime($date));
            $values[] = $lookup[$date] ?? 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    private function validDays(mixed $raw): int
    {
        $days = (int) ($raw ?? 30);
        return in_array($days, [7, 30, 90], true) ? $days : 30;
    }
}
