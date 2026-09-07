<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Profiles\Models;

 /**
 * @property int         $id
 * @property int         $user_id       Unique FK to auth users
 * @property string|null $display_name
 * @property string|null $bio
 * @property string|null $avatar        Relative path within media storage
 * @property string|null $website
 * @property string|null $twitter
 * @property string|null $facebook
 * @property string|null $linkedin
 * @property string|null $job_title
 * @property string|null $works_for
 * @property string|null $created_at
 * @property string|null $updated_at
 *
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
 */
class Profile extends \Pubvana\Models\AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'profiles', $config);
    }

    public function findByUserId(int $userId): ?self
    {
        $this->reset();
        $this->eq('user_id', $userId)->find();
        return $this->isHydrated() ? $this : null;
    }

    public function findOrCreate(int $userId): self
    {
        $profile = $this->findByUserId($userId);
        if ($profile !== null) {
            return $profile;
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $new = new self($this->getDatabaseConnection());
        $new->user_id    = $userId;
        $new->created_at = $now;
        $new->updated_at = $now;
        $new->insert();

        return $new;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateProfile(int $userId, array $data): self
    {
        $profile = $this->findOrCreate($userId);
        $profile->updateFromArray($data);
        return $profile;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateFromArray(array $data): void
    {
        $allowed = ['display_name', 'bio', 'avatar', 'website', 'twitter', 'facebook', 'linkedin', 'job_title', 'works_for'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $this->$field = trim($data[$field]) ?: null;
            }
        }
        $this->updated_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->save();
    }
}
