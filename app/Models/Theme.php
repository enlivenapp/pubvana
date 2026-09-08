<?php

declare(strict_types=1);

namespace Pubvana\Models;

/**
 * Theme ActiveRecord model.
 *
 * @property int         $id
 * @property string      $name
 * @property string      $folder
 * @property string|null $description
 * @property string|null $version
 * @property string|null $author
 * @property string|null $screenshot      Relative path within theme assets
 * @property int         $is_active       0|1
 * @property int|null    $disabled        null = not disabled, 1 = disabled
 * @property string|null $disabled_reason
 * @property string|null $installed_at
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 * @method self notEqual(string $field, mixed $value, string $operator = 'AND')
 * @method self order(string $field)
 *
 * @package Pubvana\Models
 */
class Theme extends AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'themes', $config);
    }

    public int $id;
    public string $name = '';
    public string $folder = '';
    public ?string $description = null;
    public ?string $version = null;
    public ?string $author = null;
    public ?string $screenshot = null;
    public int $is_active = 0;
    public ?int $disabled = null;
    public ?string $disabled_reason = null;
    public ?string $installed_at = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    /**
     * Find the currently active theme.
     *
     * "Not disabled" means disabled IS NULL OR disabled = 0. The schema
     * default is NULL, so themes that have never been disabled must match.
     * Treating NULL as "not disabled" lets ThemeService::getActive()
     * actually cache a result (otherwise the cache stays null forever and
     * every caller pays a round-trip).
     *
     * One query, all on ActiveRecord: a WHERE() fragment carries the OR
     * parenthesis so AND/OR precedence stays unambiguous.
     */
    public function findActive(): ?self
    {
        $model = new self($this->getDatabaseConnection());
        // WHERE clause is composed once: is_active = 1 AND (disabled is
        // null OR disabled = 0). ActiveRecord's where() SQL part inserts
        // a literal WHERE expression; conditions are appended via eq().
        $model->where('is_active = 1 AND (disabled IS NULL OR disabled = 0)')
              ->find();
        return $model->isHydrated() ? $model : null;
    }

    /**
     * Find a theme by its folder name.
     */
    public function findByFolder(string $folder): ?self
    {
        $query = new self($this->getDatabaseConnection());
        $query->eq('folder', $folder)->find();

        return $query->isHydrated() ? $query : null;
    }

    /**
     * Get all themes.
     *
     * @return self[]
     */
    public function getAll(): array
    {
        $query = new self($this->getDatabaseConnection());
        return $query->order('name ASC')->findAll();
    }

    /**
     * Deactivate all themes, then activate the given one.
     */
    public function activateById(int $id): void
    {
        $pdo = $this->getDatabaseConnection();

        $others = (new self($pdo))->notEqual('id', $id)->findAll();
        foreach ($others as $other) {
            $other->is_active = 0;
            $other->save();
        }

        $theme = new self($pdo);
        $theme->eq('id', $id)->find();
        if ($theme->isHydrated()) {
            $theme->is_active = 1;
            $theme->save();
        }
    }

    /**
     * Deactivate a theme and fall back to default.
     */
    public function deactivateAndFallback(int $id): void
    {
        $pdo = $this->getDatabaseConnection();

        $theme = new self($pdo);
        $theme->eq('id', $id)->find();
        if ($theme->isHydrated()) {
            $theme->is_active = 0;
            $theme->save();
        }

        $default = new self($pdo);
        $default->eq('folder', 'default')->find();
        if ($default->isHydrated()) {
            $default->is_active = 1;
            $default->save();
        }
    }
}
