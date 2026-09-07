<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Media\Services;

use Pubvana\Plugins\Media\Models\Media;

class MediaService
{
    private Media $model;
    private ImageProcessorInterface $processor;
    private VideoThumbnailService $videoThumb;

    /** @var array<string, mixed> */
    private array $config;

    private string $publicPath;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(\PDO $pdo, array $config, string $publicPath)
    {
        $this->model      = new Media($pdo);
        $this->config     = $config;
        $this->publicPath = rtrim($publicPath, '/');
        $this->processor  = self::createProcessor();
        $this->videoThumb = new VideoThumbnailService();
    }

    private static function createProcessor(): ImageProcessorInterface
    {
        if (extension_loaded('imagick')) {
            return new ImagickProcessor();
        }

        if (extension_loaded('gd')) {
            return new GdProcessor();
        }

        throw new \RuntimeException('No image processing extension available. Install Imagick or GD.');
    }

    // ── Upload ─────────────────────────────────────────────────

    /**
     * @param array{name: string, type: string, tmp_name: string, error: int, size: int} $file $_FILES entry
    */
    public function uploadImage(array $file, int $uploadedBy): Media
    {
        $this->validateUpload($file, 'image');

        $ext    = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $hex    = bin2hex(random_bytes(16));
        $relDir = $this->config['upload_path'] . '/' . date('Y/m');
        $absDir = $this->publicPath . '/' . $relDir;

        $this->ensureDirectory($absDir);
        $this->ensureDirectory($absDir . '/originals');
        $this->ensureDirectory($absDir . '/medium');
        $this->ensureDirectory($absDir . '/thumbs');

        $filename = $hex . '.' . $ext;

        move_uploaded_file($file['tmp_name'], $absDir . '/originals/' . $filename);
        copy($absDir . '/originals/' . $filename, $absDir . '/' . $filename);

        $this->generateDerivatives($absDir, $filename);

        return $this->model->createRecord([
            'type'        => 'image',
            'filename'    => $file['name'],
            'path'        => $relDir . '/' . $filename,
            'mime_type'   => $file['type'],
            'size'        => $file['size'],
            'uploaded_by' => $uploadedBy,
        ]);
    }

    /**
     * @param array{name: string, type: string, tmp_name: string, error: int, size: int} $file $_FILES entry
    */
    public function uploadVideo(array $file, int $uploadedBy): Media
    {
        $this->validateUpload($file, 'video');

        $ext    = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $hex    = bin2hex(random_bytes(16));
        $relDir = $this->config['upload_path'] . '/' . date('Y/m');
        $absDir = $this->publicPath . '/' . $relDir;

        $this->ensureDirectory($absDir);

        $videoName = $hex . '.' . $ext;
        $videoRel  = $relDir . '/' . $videoName;
        move_uploaded_file($file['tmp_name'], $absDir . '/' . $videoName);

        $posterPath = null;
        $posterName = $hex . '_poster.jpg';
        $posterAbs  = $absDir . '/thumbs/' . $posterName;

        $this->ensureDirectory($absDir . '/thumbs');

        if ($this->videoThumb->extract($absDir . '/' . $videoName, $posterAbs)) {
            $posterPath = $relDir . '/thumbs/' . $posterName;
        }

        return $this->model->createRecord([
            'type'        => 'video',
            'filename'    => $file['name'],
            'path'        => $videoRel,
            'mime_type'   => $file['type'],
            'size'        => $file['size'],
            'poster_path' => $posterPath,
            'uploaded_by' => $uploadedBy,
        ]);
    }

    public function storeEmbed(string $url, int $uploadedBy): Media
    {
        $provider = $this->detectProvider($url);

        return $this->model->createRecord([
            'type'           => 'embed',
            'filename'       => $url,
            'embed_url'      => $url,
            'embed_provider' => $provider,
            'uploaded_by'    => $uploadedBy,
        ]);
    }

    /**
     * @param array{name: string, type: string, tmp_name: string, error: int, size: int} $file $_FILES entry
    */
    public function uploadPoster(Media $media, array $file): Media
    {
        $this->validateUpload($file, 'image');

        $hex    = bin2hex(random_bytes(16));
        $relDir = dirname($media->path);
        $absDir = $this->publicPath . '/' . $relDir;

        $this->ensureDirectory($absDir . '/thumbs');

        if ($media->poster_path) {
            $oldPoster = $this->publicPath . '/' . $media->poster_path;
            if (file_exists($oldPoster)) {
                unlink($oldPoster);
            }
        }

        $posterName = $hex . '_poster.webp';
        $this->processor
            ->load($file['tmp_name'])
            ->resize($this->config['thumb_width'] ?? 300)
            ->toWebp($absDir . '/thumbs/' . $posterName, $this->config['webp_quality'] ?? 85);

        $media->updateMeta([
            'poster_path' => $relDir . '/thumbs/' . $posterName,
        ]);

        return $media;
    }

    // ── Editing ────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $params Operation parameters
    */
    public function applyEdit(int $id, string $operation, array $params = []): ?Media
    {
        $media = $this->model->findById($id);
        if ($media === null || $media->type !== 'image') {
            return null;
        }

        $workingPath = $this->publicPath . '/' . $media->path;
        if (!file_exists($workingPath)) {
            return null;
        }

        $this->processor->load($workingPath);

        match ($operation) {
            'crop'        => $this->processor->crop(
                (int) ($params['x'] ?? 0),
                (int) ($params['y'] ?? 0),
                (int) ($params['width'] ?? 0),
                (int) ($params['height'] ?? 0)
            ),
            'rotate'      => $this->processor->rotate((int) ($params['degrees'] ?? 90)),
            'flip'        => $this->processor->flip($params['direction'] ?? 'horizontal'),
            'sharpen'     => $this->processor->sharpen(),
            'brightness'  => $this->processor->brightness((int) ($params['level'] ?? 0)),
            'contrast'    => $this->processor->contrast((int) ($params['level'] ?? 0)),
            'auto_orient' => $this->processor->autoOrient(),
            'strip_exif'  => $this->processor->stripExif(),
            default       => throw new \InvalidArgumentException("Unknown operation: {$operation}"),
        };

        $this->processor->save($workingPath);

        $absDir   = dirname($workingPath);
        $filename = basename($media->path);
        $this->generateDerivatives($absDir, $filename);

        $media->size       = filesize($workingPath) ?: 0;
        $media->updated_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $media->save();

        return $media;
    }

    public function revert(int $id): ?Media
    {
        $media = $this->model->findById($id);
        if ($media === null || $media->type !== 'image') {
            return null;
        }

        $workingPath  = $this->publicPath . '/' . $media->path;
        $originalPath = dirname($workingPath) . '/originals/' . basename($media->path);

        if (!file_exists($originalPath)) {
            return null;
        }

        copy($originalPath, $workingPath);

        $absDir   = dirname($workingPath);
        $filename = basename($media->path);
        $this->generateDerivatives($absDir, $filename);

        $media->size       = filesize($workingPath) ?: 0;
        $media->updated_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $media->save();

        return $media;
    }

    /**
     * @return array{width: int, height: int, mime: string}|null
    */
    public function getImageInfo(int $id): ?array
    {
        $media = $this->model->findById($id);
        if ($media === null || $media->type !== 'image') {
            return null;
        }

        $workingPath = $this->publicPath . '/' . $media->path;
        if (!file_exists($workingPath)) {
            return null;
        }

        return $this->processor->getInfo($workingPath);
    }

    /**
     * @return array<string, string>
    */
    public function getExifData(int $id): array
    {
        $media = $this->model->findById($id);
        if ($media === null || $media->type !== 'image') {
            return [];
        }

        $workingPath = $this->publicPath . '/' . $media->path;
        if (!file_exists($workingPath)) {
            return [];
        }

        return $this->processor->getExif($workingPath);
    }

    /**
     * @return list<string>
    */
    public function getCapabilities(): array
    {
        return $this->processor->capabilities();
    }

    // ── Derivatives ────────────────────────────────────────────

    private function generateDerivatives(string $absDir, string $filename): void
    {
        $hex         = pathinfo($filename, PATHINFO_FILENAME);
        $workingPath = $absDir . '/' . $filename;

        $this->ensureDirectory($absDir . '/medium');
        $this->ensureDirectory($absDir . '/thumbs');

        $this->processor
            ->load($workingPath)
            ->resize($this->config['medium_width'] ?? 768)
            ->toWebp($absDir . '/medium/' . $hex . '.webp', $this->config['webp_quality'] ?? 85);

        $this->processor
            ->load($workingPath)
            ->resize($this->config['thumb_width'] ?? 300)
            ->toWebp($absDir . '/thumbs/' . $hex . '.webp', $this->config['webp_quality'] ?? 85);
    }

    // ── Metadata ───────────────────────────────────────────────

    /**
     * @param array<string, mixed> $data Whitelisted keys only
    */
    public function updateMeta(int $id, array $data): ?Media
    {
        $media = $this->model->findById($id);
        if ($media === null) {
            return null;
        }

        $media->updateMeta($data);
        return $media;
    }

    // ── Deletion ───────────────────────────────────────────────

    public function delete(int $id): bool
    {
        $media = $this->model->findById($id);
        if ($media === null) {
            return false;
        }

        if ($media->path) {
            $basePath = $this->publicPath . '/' . $media->path;
            $dir      = dirname($basePath);
            $hex      = pathinfo($basePath, PATHINFO_FILENAME);

            if (file_exists($basePath)) {
                unlink($basePath);
            }

            $original = $dir . '/originals/' . basename($basePath);
            if (file_exists($original)) {
                unlink($original);
            }

            $medium = $dir . '/medium/' . $hex . '.webp';
            if (file_exists($medium)) {
                unlink($medium);
            }

            $thumb = $dir . '/thumbs/' . $hex . '.webp';
            if (file_exists($thumb)) {
                unlink($thumb);
            }
        }

        if ($media->poster_path) {
            $poster = $this->publicPath . '/' . $media->poster_path;
            if (file_exists($poster)) {
                unlink($poster);
            }
        }

        $media->delete();
        return true;
    }

    // ── Queries ────────────────────────────────────────────────

    /**
     * @return array{items: array<int, Media>, total: int, page: int, per_page: int}
    */
    public function list(int $page = 1, int $perPage = 24, ?string $type = null): array
    {
        return [
            'items'    => $this->model->paginate($page, $perPage, $type),
            'total'    => $this->model->countAll($type),
            'page'     => $page,
            'per_page' => $perPage,
        ];
    }

    public function countAll(?string $type = null): int
    {
        return $this->model->countAll($type);
    }

    /**
     * @return array<int, Media>
    */
    public function recent(int $limit = 5, ?string $type = null): array
    {
        return $this->model->paginate(1, $limit, $type);
    }

    public function find(int $id): ?Media
    {
        return $this->model->findById($id);
    }

    // ── Widgets ────────────────────────────────────────────────

    private function adminBase(): string
    {
        $prefix = rtrim((string) ($this->config['route_prefix'] ?? ''), '/');
        if ($prefix === '') {
            $prefix = '/' . trim($this->config['routePrepend'] ?? 'media', '/');
        }
        return '/admin' . $prefix;
    }

    /**
     * @return string Rendered widget HTML
    */
    public function picker(string $inputName, string $currentValue = ''): string
    {
        static $counter = 0;
        $pickerId = 'media-picker-' . (++$counter);
        $adminBase = $this->adminBase();

        ob_start();
        include __DIR__ . '/../Views/admin/picker.php';
        return is_string($html = ob_get_clean()) ? $html : '';
    }

    /**
     * @return string Rendered widget HTML
    */
    public function avatarPicker(string $inputName, string $currentValue = ''): string
    {
        static $counter = 0;
        $pickerId = 'avatar-picker-' . (++$counter);
        $adminBase = $this->adminBase();

        ob_start();
        include __DIR__ . '/../Views/admin/avatar-picker.php';
        return is_string($html = ob_get_clean()) ? $html : '';
    }

    /**
     * @param array<string, mixed> $options
     * @return string Rendered init snippet
    */
    public function joditInit(string $selector, array $options = []): string
    {
        static $counter = 0;
        $joditId = 'jodit-media-' . (++$counter);

        $defaults = [
            'height'  => 500,
            'buttons' => 'bold,italic,underline,strikethrough,|,ul,ol,|,outdent,indent,|,font,fontsize,brush,paragraph,|,image,video,table,link,|,align,undo,redo,|,hr,symbol,fullsize,source',
        ];
        $config = array_merge($defaults, $options);
        $adminBase = $this->adminBase();

        ob_start();
        include __DIR__ . '/../Views/admin/jodit.php';
        return is_string($html = ob_get_clean()) ? $html : '';
    }

    // ── Internal ───────────────────────────────────────────────

    /**
     * @param array{name: string, type: string, tmp_name: string, error: int, size: int} $file $_FILES entry
    */
    private function validateUpload(array $file, string $kind): void
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('Upload failed with error code: ' . $file['error']);
        }

        $ext        = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedKey = ($kind === 'image') ? 'allowed_image_ext' : 'allowed_video_ext';
        $maxKey     = ($kind === 'image') ? 'max_image_size' : 'max_video_size';

        if (!in_array($ext, $this->config[$allowedKey] ?? [], true)) {
            throw new \InvalidArgumentException("File type not allowed: .{$ext}");
        }

        if ($file['size'] > ($this->config[$maxKey] ?? 0)) {
            $maxMb = round(($this->config[$maxKey] ?? 0) / 1024 / 1024);
            throw new \InvalidArgumentException("File exceeds maximum size of {$maxMb} MB.");
        }

        $finfo     = new \finfo(FILEINFO_MIME_TYPE);
        $actualMime = $finfo->file($file['tmp_name']);

        $allowedMimes = ($kind === 'image')
            ? ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
            : ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-m4v', 'application/mp4'];

        if (!in_array($actualMime, $allowedMimes, true)) {
            throw new \InvalidArgumentException('File content does not match an allowed type.');
        }
    }

    private function detectProvider(string $url): ?string
    {
        if (preg_match('/youtube\.com|youtu\.be/i', $url)) {
            return 'youtube';
        }

        if (preg_match('/vimeo\.com/i', $url)) {
            return 'vimeo';
        }

        return null;
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}
