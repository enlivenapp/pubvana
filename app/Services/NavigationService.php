<?php

namespace App\Services;

class NavigationService
{
    public function getTree(string $group = 'primary'): array
    {
        $db   = db_connect();
        $flat = $db->table('navigation')
            ->where('nav_group', $group)
            ->orderBy('sort_order', 'ASC')
            ->get()->getResultObject();

        return $this->buildTree($flat);
    }

    protected function buildTree(array $flat, int $parentId = 0): array
    {
        $tree = [];
        foreach ($flat as $item) {
            $pid = (int) ($item->parent_id ?? 0);
            if ($pid === $parentId) {
                $item->children = $this->buildTree($flat, (int) $item->id);
                $tree[]         = $item;
            }
        }
        return $tree;
    }
}
