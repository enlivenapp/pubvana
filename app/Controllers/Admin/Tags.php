<?php

namespace App\Controllers\Admin;

use App\Models\TagModel;

class Tags extends BaseAdminController
{
    public function index(): string
    {
        if (! auth()->user()->can('posts.edit.any')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        $tags = (new TagModel())->getWithPostCount();
        return $this->adminView('tags/index', array_merge($this->baseData('Tags', 'tags'), ['tags' => $tags]));
    }

    public function delete(int $id)
    {
        if (! auth()->user()->can('posts.edit.any')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        (new TagModel())->delete($id);
        db_connect()->table('tags_to_posts')->where('tag_id', $id)->delete();
        return redirect()->to('/admin/tags')->with('success', 'Tag deleted.');
    }
}
