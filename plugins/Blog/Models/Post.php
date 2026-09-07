<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Blog\Models;

/**
 * @property int         $id
 * @property string      $title
 * @property string      $slug
 * @property string|null $content
 * @property string|null $excerpt
 * @property string      $status
 * @property string|null $featured_image
 * @property int|null    $media_id
 * @property int         $author_id
 * @property string|null $published_at
 * @property int         $views
 * @property int         $is_featured
 * @property int         $allow_comments
 * @property int         $ai_generated
 * @property string|null $preview_token
 * @property string      $created_at
 * @property string      $updated_at
 * @property string|null $deleted_at
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 * @method self notEq(string $field, mixed $value, string $operator = 'AND')
 * @method self like(string $field, mixed $value, string $operator = 'AND')
 * @method self in(string $field, mixed $value, string $operator = 'AND')
 * @method self isNull(string $field, string $operator = 'AND')
 * @method self order(string $field)
 * @method self select(string $field, string ...$fields)
 * @method self limit(int $limit)
 * @method self offset(int $offset)
 * @method self startWrap()
 * @method self endWrap(string $op)
  *
 * @property int $cnt Aggregate alias from COUNT(*) selects
*/
class Post extends \Pubvana\Models\AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'posts', $config);
    }

    public function findById(int $id): ?self
    {
        $this->reset();
        $this->eq('id', $id)->isNull('deleted_at')->find();
        return $this->isHydrated() ? $this : null;
    }

    public function findBySlug(string $slug): ?self
    {
        $this->reset();
        $this->eq('slug', $slug)
             ->eq('status', 'published')
             ->isNull('deleted_at')
             ->find();
        return $this->isHydrated() ? $this : null;
    }

    public function findByPreviewToken(string $token): ?self
    {
        $this->reset();
        $this->eq('preview_token', $token)->isNull('deleted_at')->find();
        return $this->isHydrated() ? $this : null;
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = new self($this->getDatabaseConnection());
        $query->select('COUNT(*) as cnt')
              ->eq('slug', $slug)
              ->isNull('deleted_at');

        if ($excludeId !== null) {
            $query->notEq('id', $excludeId);
        }

        $result = $query->find();
        return ((int) $result->cnt) > 0;
    }

    /**
     * @return array<int, Post>
     */
    public function paginate(int $page = 1, int $perPage = 25, ?string $status = null): array
    {
        $query = new self($this->getDatabaseConnection());
        $query->isNull('deleted_at');

        if ($status !== null) {
            $query->eq('status', $status);
        }

        return $query->order('id DESC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->findAll();
    }

    /**
     * Most recent published posts, newest first, no count query.
     *
     * @return array<int, Post>
     */
    public function publishedRecent(int $limit = 5): array
    {
        $query = new self($this->getDatabaseConnection());
        return $query->eq('status', 'published')
            ->isNull('deleted_at')
            ->order('id DESC')
            ->limit($limit)
            ->findAll();
    }

    public function countAll(?string $status = null): int
    {
        $query = new self($this->getDatabaseConnection());
        $query->select('COUNT(*) as cnt')->isNull('deleted_at');

        if ($status !== null) {
            $query->eq('status', $status);
        }

        $result = $query->find();
        return (int) $result->cnt;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createRecord(array $data): self
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $record = new self($this->getDatabaseConnection());

        foreach ($data as $key => $value) {
            $record->$key = $value;
        }

        $record->created_at = $now;
        $record->updated_at = $now;
        $record->insert();

        return $record;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateRecord(array $data): void
    {
        $allowed = [
            'title', 'content', 'excerpt', 'status', 'featured_image',
            'media_id', 'published_at', 'is_featured', 'allow_comments',
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $this->$field = $data[$field];
            }
        }

        $this->updated_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->save();
    }

    public function softDelete(): void
    {
        $this->deleted_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->save();
    }

    public function incrementViews(): void
    {
        $this->views = (int) $this->views + 1;
        $this->save();
    }

    /**
     * Atomic increment without hydrating. Saves the SELECT round-trip that
     * BlogService::recordView() used to make. No-op for missing/tombstoned rows.
     *
     * Raw SQL is unavoidable here: ActiveRecord::save() requires a hydrated
     * row, and there is no fluent "UPDATE ... WHERE id = ? AND deleted_at IS
     * NULL" builder. Hydrating just to bump a counter would be the N+1 we are
     * specifically trying to remove.
     */
    public function incrementViewsDirect(int $id): void
    {
        $pdo = $this->getDatabaseConnection();
        $stmt = $pdo->prepare(
            'UPDATE posts SET views = views + 1, updated_at = updated_at '
            . 'WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([':id' => $id]);
    }

    public function generatePreviewToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->preview_token = $token;
        $this->save();
        return $token;
    }
}
