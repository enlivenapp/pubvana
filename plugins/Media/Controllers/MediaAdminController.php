<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Media\Controllers;

use Pubvana\Controllers\Admin\AdminController;
use Pubvana\Plugins\Media\Services\MediaService;

class MediaAdminController extends AdminController
{
    private function adminBase(): string
    {
        return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/media'), '/');
    }

    protected function service(): MediaService
    {
        return $this->app->media();
    }

    public function index(): void
    {
        $request = $this->app->request();
        $page    = max(1, (int) ($request->query->page ?? 1));
        $type    = $request->query->type ?? null;

        if ($type !== null && !in_array($type, ['image', 'video', 'embed'], true)) {
            $type = null;
        }

        $result = $this->service()->list($page, 24, $type);

        $this->render('pubvana/media/admin/index', [
            'pageTitle'  => 'Media Library',
            'adminBase'  => $this->adminBase(),
            'media'      => $result['items'],
            'total'      => $result['total'],
            'page'       => $result['page'],
            'perPage'    => $result['per_page'],
            'typeFilter' => $type,
        ]);
    }

    public function json(): void
    {
        $request = $this->app->request();
        $page    = max(1, (int) ($request->query->page ?? 1));
        $type    = $request->query->type ?? null;

        if ($type !== null && !in_array($type, ['image', 'video', 'embed'], true)) {
            $type = null;
        }

        $result = $this->service()->list($page, 24, $type);

        $items = array_map(function ($m) {
            return $this->mediaToArray($m);
        }, $result['items']);

        $this->app->json([
            'items'    => $items,
            'total'    => $result['total'],
            'page'     => $result['page'],
            'per_page' => $result['per_page'],
        ]);
    }

    public function uploadImage(): void
    {
        $file = $this->app->request()->files->file ?? null;

        if ($file === null || $file['error'] !== UPLOAD_ERR_OK) {
            $this->app->json(['error' => 'No file uploaded or upload error.'], 400);
            return;
        }

        try {
            $user = $this->app->auth()->user();
            $media = $this->service()->uploadImage($file, (int) ($user->id ?? 0));
            $this->app->json($this->mediaToArray($media), 201);
        } catch (\InvalidArgumentException $e) {
            $this->app->json(['error' => $e->getMessage()], 422);
        }
    }

    public function uploadVideo(): void
    {
        $file = $this->app->request()->files->file ?? null;

        if ($file === null || $file['error'] !== UPLOAD_ERR_OK) {
            $this->app->json(['error' => 'No file uploaded or upload error.'], 400);
            return;
        }

        try {
            $user = $this->app->auth()->user();
            $media = $this->service()->uploadVideo($file, (int) ($user->id ?? 0));
            $this->app->json($this->mediaToArray($media), 201);
        } catch (\InvalidArgumentException $e) {
            $this->app->json(['error' => $e->getMessage()], 422);
        }
    }

    public function storeEmbed(): void
    {
        $url = trim($this->app->request()->data->url ?? '');

        if ($url === '') {
            $this->app->json(['error' => 'URL is required.'], 400);
            return;
        }

        $user = $this->app->auth()->user();
        $media = $this->service()->storeEmbed($url, (int) ($user->id ?? 0));

        $this->app->json($this->mediaToArray($media), 201);
    }

    public function uploadPoster(string $id): void
    {
        $media = $this->service()->find((int) $id);

        if ($media === null || $media->type !== 'video') {
            $this->app->json(['error' => 'Video not found.'], 404);
            return;
        }

        $file = $this->app->request()->files->file ?? null;

        if ($file === null || $file['error'] !== UPLOAD_ERR_OK) {
            $this->app->json(['error' => 'No file uploaded.'], 400);
            return;
        }

        try {
            $media = $this->service()->uploadPoster($media, $file);
            $this->app->json($this->mediaToArray($media));
        } catch (\InvalidArgumentException $e) {
            $this->app->json(['error' => $e->getMessage()], 422);
        }
    }

    public function update(string $id): void
    {
        $data = $this->app->request()->data->getData();
        unset($data['_csrf_token']);

        $media = $this->service()->updateMeta((int) $id, $data);

        if ($media === null) {
            $this->app->json(['error' => 'Media not found.'], 404);
            return;
        }

        $this->app->json($this->mediaToArray($media));
    }

    public function destroy(string $id): void
    {
        if (!$this->service()->delete((int) $id)) {
            $this->app->json(['error' => 'Media not found.'], 404);
            return;
        }

        $this->app->json(['success' => true]);
    }

    public function editor(string $id): void
    {
        $media = $this->service()->find((int) $id);

        if ($media === null || $media->type !== 'image') {
            $this->app->redirect($this->adminBase());
            return;
        }

        $info         = $this->service()->getImageInfo((int) $id);
        $capabilities = $this->service()->getCapabilities();
        $exifData     = $this->service()->getExifData((int) $id);

        $this->render('pubvana/media/admin/editor', [
            'pageTitle'    => 'Edit — ' . ($media->title ?: $media->filename),
            'adminBase'    => $this->adminBase(),
            'media'        => $media,
            'info'         => $info,
            'capabilities' => $capabilities,
            'exifData'     => $exifData,
        ]);
    }

    public function applyEdit(string $id): void
    {
        $data      = $this->app->request()->data;
        $operation = trim($data->operation ?? '');
        $params    = json_decode($data->params ?? '{}', true) ?: [];

        $caps = $this->service()->getCapabilities();
        if (!in_array($operation, $caps, true)) {
            $this->app->json(['error' => 'Unsupported operation.'], 400);
            return;
        }

        try {
            $media = $this->service()->applyEdit((int) $id, $operation, $params);
        } catch (\InvalidArgumentException $e) {
            $this->app->json(['error' => $e->getMessage()], 422);
            return;
        }

        if ($media === null) {
            $this->app->json(['error' => 'Image not found.'], 404);
            return;
        }

        $info   = $this->service()->getImageInfo((int) $id);
        $result = $this->mediaToArray($media);
        $result['info'] = $info;
        $result['exif'] = $this->service()->getExifData((int) $id);

        $this->app->json($result);
    }

    public function revert(string $id): void
    {
        $media = $this->service()->revert((int) $id);

        if ($media === null) {
            $this->app->json(['error' => 'Image not found or no original available.'], 404);
            return;
        }

        $info   = $this->service()->getImageInfo((int) $id);
        $result = $this->mediaToArray($media);
        $result['info'] = $info;
        $result['exif'] = $this->service()->getExifData((int) $id);

        $this->app->json($result);
    }

    public function capabilities(): void
    {
        $this->app->json(['capabilities' => $this->service()->getCapabilities()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mediaToArray(\Pubvana\Plugins\Media\Models\Media $media): array
    {
        $data = [
            'id'             => (int) $media->id,
            'type'           => $media->type,
            'filename'       => $media->filename,
            'path'           => $media->path,
            'mime_type'      => $media->mime_type,
            'size'           => $media->size ? (int) $media->size : null,
            'alt_text'       => $media->alt_text,
            'title'          => $media->title,
            'embed_url'      => $media->embed_url,
            'embed_provider' => $media->embed_provider,
            'poster_path'    => $media->poster_path,
            'uploaded_by'    => (int) $media->uploaded_by,
            'created_at'     => $media->created_at,
            'updated_at'     => $media->updated_at,
        ];

        if ($media->type === 'image' && $media->path) {
            $dir  = dirname($media->path);
            $name = pathinfo($media->path, PATHINFO_FILENAME);

            $data['url']         = '/' . $media->path;
            $data['thumb_path']  = $dir . '/thumbs/' . $name . '.webp';
            $data['medium_path'] = $dir . '/medium/' . $name . '.webp';
            $data['thumb_url']   = '/' . $dir . '/thumbs/' . $name . '.webp';
            $data['medium_url']  = '/' . $dir . '/medium/' . $name . '.webp';
        } elseif ($media->path) {
            $data['url'] = '/' . $media->path;
        }

        if ($media->poster_path) {
            $data['poster_url'] = '/' . $media->poster_path;
        }

        return $data;
    }
}
