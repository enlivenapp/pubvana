<?php

namespace App\Controllers\Admin;

use App\Models\TagModel;

class Tags extends BaseAdminController
{
    public function index(): string
    {
        $tags = (new TagModel())->getWithPostCount();
        return $this->adminView('tags/index', array_merge($this->baseData('Tags', 'tags'), ['tags' => $tags]));
    }

    public function delete(int $id)
    {
        (new TagModel())->delete($id);
        db_connect()->table('tags_to_posts')->where('tag_id', $id)->delete();
        return redirect()->to('/admin/tags')->with('success', 'Tag deleted.');
    }
}
