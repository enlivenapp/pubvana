<?php

namespace App\Controllers\Admin;

use App\Models\PageModel;
use App\Services\ActivityLogger;

class Pages extends BaseAdminController
{
    protected PageModel $pageModel;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->pageModel = new PageModel();
    }

    public function index(): string
    {
        if (! auth()->user()->can('pages.manage')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        $pages = $this->pageModel->orderBy('sort_order')->findAll();
        return $this->adminView('pages/index', array_merge($this->baseData('Pages', 'pages'), ['pages' => $pages]));
    }

    public function create(): string
    {
        if (! auth()->user()->can('pages.manage')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        return $this->adminView('pages/create', $this->baseData('New Page', 'pages'));
    }

    public function store()
    {
        if (! auth()->user()->can('pages.manage')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        if (! $this->validate(['title' => 'required|max_length[255]', 'slug' => 'permit_empty|max_length[255]'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $slug = slug_from_title($this->request->getPost('slug') ?: $this->request->getPost('title'));
        if ($this->pageModel->findBySlug($slug)) {
            return redirect()->back()->withInput()->with('error', "Slug '{$slug}' already in use.");
        }
        $contentType = $this->request->getPost('content_type') ?? 'html';
        $content     = $contentType === 'markdown'
            ? $this->request->getPost('content_md')
            : $this->request->getPost('content');
        $this->pageModel->insert([
            'title'            => $this->request->getPost('title'),
            'slug'             => $slug,
            'content'          => $content,
            'content_type'     => $contentType,
            'status'           => $this->request->getPost('status') ?? 'draft',
            'meta_title'       => $this->request->getPost('meta_title'),
            'meta_description' => $this->request->getPost('meta_description'),
            'is_system'        => 0,
        ]);
        $newId = $this->pageModel->getInsertID();
        ActivityLogger::log('page.created', 'page', $newId ?: null, 'Created page: ' . $this->request->getPost('title'));
        return redirect()->to('/admin/pages')->with('success', 'Page created.');
    }

    public function edit(int $id): string
    {
        if (! auth()->user()->can('pages.manage')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        $page = $this->pageModel->find($id);
        if (! $page) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        return $this->adminView('pages/edit', array_merge($this->baseData('Edit Page', 'pages'), ['page' => $page]));
    }

    public function update(int $id)
    {
        if (! auth()->user()->can('pages.manage')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        $page = $this->pageModel->find($id);
        if (! $page) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        if (! $this->validate(['title' => 'required|max_length[255]'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $contentType = $this->request->getPost('content_type') ?? 'html';
        $content     = $contentType === 'markdown'
            ? $this->request->getPost('content_md')
            : $this->request->getPost('content');
        $this->pageModel->update($id, [
            'title'            => $this->request->getPost('title'),
            'content'          => $content,
            'content_type'     => $contentType,
            'status'           => $this->request->getPost('status') ?? 'draft',
            'meta_title'       => $this->request->getPost('meta_title'),
            'meta_description' => $this->request->getPost('meta_description'),
        ]);
        ActivityLogger::log('page.updated', 'page', $id, 'Updated page: ' . $this->request->getPost('title'));
        return redirect()->to('/admin/pages')->with('success', 'Page updated.');
    }

    public function delete(int $id)
    {
        if (! auth()->user()->can('pages.manage')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        $page = $this->pageModel->find($id);
        if (! $page || $page->is_system) {
            return redirect()->to('/admin/pages')->with('error', 'Cannot delete this page.');
        }
        $this->pageModel->delete($id);
        ActivityLogger::log('page.deleted', 'page', $id, 'Deleted page: ' . $page->title);
        return redirect()->to('/admin/pages')->with('success', 'Page deleted.');
    }
}
