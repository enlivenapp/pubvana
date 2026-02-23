<?php

namespace App\Controllers\Admin;

use App\Models\CategoryModel;

class Categories extends BaseAdminController
{
    protected CategoryModel $model;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->model = new CategoryModel();
    }

    public function index(): string
    {
        return $this->adminView('categories/index', array_merge($this->baseData('Categories', 'categories'), [
            'categories' => $this->model->getWithPostCount(),
        ]));
    }

    public function create(): string
    {
        return $this->adminView('categories/create', $this->baseData('New Category', 'categories'));
    }

    public function store()
    {
        if (! $this->validate(['name' => 'required|max_length[255]'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $slug = slug_from_title($this->request->getPost('name'));
        $this->model->insert([
            'name'        => $this->request->getPost('name'),
            'slug'        => $slug,
            'description' => $this->request->getPost('description'),
        ]);
        return redirect()->to('/admin/categories')->with('success', 'Category created.');
    }

    public function edit(int $id): string
    {
        $cat = $this->model->find($id);
        if (! $cat) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        return $this->adminView('categories/edit', array_merge($this->baseData('Edit Category', 'categories'), ['category' => $cat]));
    }

    public function update(int $id)
    {
        if (! $this->validate(['name' => 'required|max_length[255]'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $this->model->update($id, [
            'name'        => $this->request->getPost('name'),
            'slug'        => slug_from_title($this->request->getPost('name')),
            'description' => $this->request->getPost('description'),
        ]);
        return redirect()->to('/admin/categories')->with('success', 'Category updated.');
    }

    public function delete(int $id)
    {
        $this->model->delete($id);
        return redirect()->to('/admin/categories')->with('success', 'Category deleted.');
    }
}
