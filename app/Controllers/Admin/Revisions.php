<?php

namespace App\Controllers\Admin;

class Revisions extends BaseAdminController
{
    public function index(int $postId): string
    {
        $db   = db_connect();
        $post = $db->table('posts')->where('id', $postId)->get()->getRowObject();
        if (! $post) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        if (! auth()->user()->can('posts.edit.any') && (int) $post->author_id !== auth()->id()) {
            return redirect()->to('/admin/posts')->with('error', 'Permission denied.');
        }

        $revisions = $db->table('post_revisions pr')
            ->select('pr.*, u.username as author_name')
            ->join('users u', 'u.id = pr.author_id', 'left')
            ->where('pr.post_id', $postId)
            ->orderBy('pr.id', 'DESC')
            ->get()->getResultObject();

        return $this->adminView('posts/revisions', array_merge($this->baseData('Revisions — ' . $post->title, 'posts'), [
            'post'      => $post,
            'revisions' => $revisions,
        ]));
    }

    public function show(int $revisionId): string
    {
        $db       = db_connect();
        $revision = $db->table('post_revisions pr')
            ->select('pr.*, u.username as author_name')
            ->join('users u', 'u.id = pr.author_id', 'left')
            ->where('pr.id', $revisionId)
            ->get()->getRowObject();

        if (! $revision) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $post = $db->table('posts')->where('id', $revision->post_id)->get()->getRowObject();
        if ($post && ! auth()->user()->can('posts.edit.any') && (int) $post->author_id !== auth()->id()) {
            return redirect()->to('/admin/posts')->with('error', 'Permission denied.');
        }

        return $this->adminView('posts/revision_show', array_merge($this->baseData('Revision — ' . $revision->title, 'posts'), [
            'revision' => $revision,
            'post'     => $post,
        ]));
    }

    public function restore(int $revisionId)
    {
        $db       = db_connect();
        $revision = $db->table('post_revisions')->where('id', $revisionId)->get()->getRowObject();
        if (! $revision) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $post = $db->table('posts')->where('id', $revision->post_id)->get()->getRowObject();
        if ($post && ! auth()->user()->can('posts.edit.any') && (int) $post->author_id !== auth()->id()) {
            return redirect()->to('/admin/posts')->with('error', 'Permission denied.');
        }

        $db->table('posts')->where('id', $revision->post_id)->update([
            'title'            => $revision->title,
            'content'          => $revision->content,
            'content_type'     => $revision->content_type,
            'excerpt'          => $revision->excerpt,
            'status'           => $revision->status,
            'meta_title'       => $revision->meta_title,
            'meta_description' => $revision->meta_description,
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/admin/posts/' . $revision->post_id . '/revisions')
                         ->with('success', 'Post restored to revision from ' . $revision->created_at . '.');
    }
}
