<?php

namespace App\Controllers\Admin;

use App\Models\MediaModel;
use App\Services\MediaService;

class Media extends BaseAdminController
{
    public function index(): string
    {
        $model  = new MediaModel();
        $media  = $model->orderBy('created_at', 'DESC')->paginate(24);
        return $this->adminView('media/index', array_merge($this->baseData('Media Library', 'media'), [
            'media' => $media,
            'pager' => $model->pager,
        ]));
    }

    public function upload()
    {
        $file = $this->request->getFile('file');
        if (! $file || ! $file->isValid()) {
            return $this->response->setJSON(['error' => 'No valid file uploaded.'])->setStatusCode(400);
        }
        try {
            $service = new MediaService();
            $result  = $service->upload($file, (int) auth()->id());
            return $this->response->setJSON(['success' => true, 'url' => $result['url'], 'path' => $result['path']]);
        } catch (\RuntimeException $e) {
            return $this->response->setJSON(['error' => $e->getMessage()])->setStatusCode(422);
        }
    }

    public function delete(int $id)
    {
        (new MediaService())->delete($id);
        return redirect()->to('/admin/media')->with('success', 'Media deleted.');
    }
}
