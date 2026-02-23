<?php

namespace App\Controllers\Admin;

use App\Models\PageModel;

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
        $pages = $this->pageModel->orderBy('sort_order')->findAll();
        return $this->adminView('pages/index', array_merge($this->baseData('Pages', 'pages'), ['pages' => $pages]));
    }

    public function create(): string
    {
        return $this->adminView('pages/create', $this->baseData('New Page', 'pages'));
    }

    public function store()
    {
        if (! $this->validate(['title' => 'required|max_length[255]', 'slug' => 'required|max_length[255]'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $slug = slug_from_title($this->request->getPost('slug') ?: $this->request->getPost('title'));
        if ($this->pageModel->findBySlug($slug)) {
            return redirect()->back()->withInput()->with('error', "Slug '{$slug}' already in use.");
        }
        $this->pageModel->insert([
            'title'            => $this->request->getPost('title'),
            'slug'             => $slug,
            'content'          => $this->request->getPost('content'),
            'content_type'     => $this->request->getPost('content_type') ?? 'html',
            'status'           => $this->request->getPost('status') ?? 'draft',
            'meta_title'       => $this->request->getPost('meta_title'),
            'meta_description' => $this->request->getPost('meta_description'),
            'is_system'        => 0,
        ]);
        return redirect()->to('/admin/pages')->with('success', 'Page created.');
    }

    public function edit(int $id): string
    {
        $page = $this->pageModel->find($id);
        if (! $page) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        return $this->adminView('pages/edit', array_merge($this->baseData('Edit Page', 'pages'), ['page' => $page]));
    }

    public function update(int $id)
    {
        $page = $this->pageModel->find($id);
        if (! $page) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        if (! $this->validate(['title' => 'required|max_length[255]'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $this->pageModel->update($id, [
            'title'            => $this->request->getPost('title'),
            'content'          => $this->request->getPost('content'),
            'content_type'     => $this->request->getPost('content_type') ?? 'html',
            'status'           => $this->request->getPost('status') ?? 'draft',
            'meta_title'       => $this->request->getPost('meta_title'),
            'meta_description' => $this->request->getPost('meta_description'),
        ]);
        return redirect()->to('/admin/pages')->with('success', 'Page updated.');
    }

    public function delete(int $id)
    {
        $page = $this->pageModel->find($id);
        if (! $page || $page->is_system) {
            return redirect()->to('/admin/pages')->with('error', 'Cannot delete this page.');
        }
        $this->pageModel->delete($id);
        return redirect()->to('/admin/pages')->with('success', 'Page deleted.');
    }
}
