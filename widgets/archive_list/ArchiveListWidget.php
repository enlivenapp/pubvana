<?php

use App\Libraries\BaseWidget;

class ArchiveListWidget extends BaseWidget
{
    protected string $folder = 'archive_list';

    public function getInfo(): array
    {
        return require __DIR__ . '/widget_info.php';
    }

    protected function buildOutput(array $options): string
    {
        $format = $options['format'] ?? 'monthly';
        if ($format === 'yearly') {
            $rows = db_connect()->table('posts')
                ->select('YEAR(published_at) as year, COUNT(*) as count')
                ->where('status', 'published')
                ->where('deleted_at IS NULL')
                ->groupBy('YEAR(published_at)')
                ->orderBy('year', 'DESC')
                ->limit(10)
                ->get()->getResultObject();
            foreach ($rows as $r) {
                $r->url   = base_url('archive/' . $r->year . '/1');
                $r->label = $r->year;
            }
        } else {
            $rows = db_connect()->table('posts')
                ->select('YEAR(published_at) as year, MONTH(published_at) as month, COUNT(*) as count')
                ->where('status', 'published')
                ->where('deleted_at IS NULL')
                ->groupBy(['YEAR(published_at)', 'MONTH(published_at)'])
                ->orderBy('year', 'DESC')
                ->orderBy('month', 'DESC')
                ->limit(12)
                ->get()->getResultObject();
            foreach ($rows as $r) {
                $r->url   = base_url('archive/' . $r->year . '/' . $r->month);
                $r->label = date('F Y', mktime(0, 0, 0, $r->month, 1, $r->year));
            }
        }

        return $this->view('widget', array_merge($options, ['rows' => $rows]));
    }
}
