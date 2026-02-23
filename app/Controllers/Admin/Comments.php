<?php

namespace App\Controllers\Admin;

use App\Models\CommentModel;

class Comments extends BaseAdminController
{
    public function index(): string
    {
        if (! auth()->user()->can('comments.moderate')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        $filter   = $this->request->getGet('status') ?? 'pending';
        $model    = new CommentModel();
        $comments = $model->where('status', $filter)->orderBy('created_at', 'DESC')->paginate(20);
        return $this->adminView('comments/index', array_merge($this->baseData('Comments', 'comments'), [
            'comments' => $comments,
            'pager'    => $model->pager,
            'filter'   => $filter,
        ]));
    }

    public function approve(int $id)
    {
        if (! auth()->user()->can('comments.moderate')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        (new CommentModel())->update($id, ['status' => 'approved']);
        return redirect()->back()->with('success', 'Comment approved.');
    }

    public function spam(int $id)
    {
        if (! auth()->user()->can('comments.moderate')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        (new CommentModel())->update($id, ['status' => 'spam']);
        return redirect()->back()->with('success', 'Marked as spam.');
    }

    public function trash(int $id)
    {
        if (! auth()->user()->can('comments.moderate')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        (new CommentModel())->update($id, ['status' => 'trash']);
        return redirect()->back()->with('success', 'Comment trashed.');
    }

    public function delete(int $id)
    {
        if (! auth()->user()->can('comments.moderate')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        (new CommentModel())->delete($id);
        return redirect()->back()->with('success', 'Comment deleted permanently.');
    }
}
