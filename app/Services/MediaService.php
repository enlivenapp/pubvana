<?php

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Services;

class MediaService
{
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const MAX_SIZE_KB  = 10240; // 10 MB

    private const SIZES = [
        'thumbnail' => [300, 200],
        'medium'    => [800, 600],
    ];

    public function upload(UploadedFile $file, int $uploadedBy = 0): array
    {
        if (! $file->isValid() || $file->hasMoved()) {
            throw new \RuntimeException('Invalid or already-moved upload.');
        }
        if (! in_array($file->getMimeType(), self::ALLOWED_MIME, true)) {
            throw new \RuntimeException('Only JPEG, PNG, WebP, and GIF images are accepted.');
        }
        if ($file->getSizeByUnit('kb') > self::MAX_SIZE_KB) {
            throw new \RuntimeException('Image must be 10 MB or smaller.');
        }

        // Capture these before move() — temp file is gone afterwards
        $mimeType  = $file->getMimeType();
        $fileSize  = $file->getSize();
        $origName  = $file->getName();

        $ext     = $this->mimeToExt($mimeType);
        $name    = bin2hex(random_bytes(16));
        $relDir  = 'uploads/' . date('Y/m');
        $absDir  = WRITEPATH . $relDir;

        if (! is_dir($absDir)) {
            mkdir($absDir, 0755, true);
        }

        $tmpPath = WRITEPATH . 'tmp/' . 'tmp_' . $name . '.' . $ext;
        $file->move(WRITEPATH . 'tmp/', 'tmp_' . $name . '.' . $ext);

        $relPath = $relDir . '/' . $name . '.' . $ext;
        $absPath = WRITEPATH . $relPath;

        Services::image('gd')
            ->withFile($tmpPath)
            ->resize(1920, 1200, true, 'width')
            ->save($absPath, 85);

        // Create thumbnail
        $thumbDir  = $absDir . '/thumbs';
        $thumbPath = $thumbDir . '/' . $name . '.' . $ext;
        if (! is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }
        Services::image('gd')
            ->withFile($tmpPath)
            ->fit(300, 200, 'center')
            ->save($thumbPath, 80);

        @unlink($tmpPath);

        $mediaId = db_connect()->table('media')->insert([
            'filename'    => $origName,
            'path'        => $relPath,
            'mime_type'   => $mimeType,
            'size'        => $fileSize,
            'uploaded_by' => $uploadedBy,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ], true);

        return [
            'id'   => $mediaId,
            'path' => $relPath,
            'url'  => base_url('writable/' . $relPath),
        ];
    }

    public function delete(int $id): bool
    {
        $db    = db_connect();
        $media = $db->table('media')->where('id', $id)->get()->getRowObject();
        if (! $media) {
            return false;
        }
        $abs = WRITEPATH . ltrim($media->path, '/');
        if (is_file($abs)) {
            @unlink($abs);
        }
        $db->table('media')->where('id', $id)->delete();
        return true;
    }

    private function mimeToExt(string $mime): string
    {
        return match ($mime) {
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => 'jpg',
        };
    }
}
