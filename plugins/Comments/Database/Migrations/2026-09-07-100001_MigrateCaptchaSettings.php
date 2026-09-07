<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Comments\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;
use Pubvana\Models\Setting;

/**
 * MigrateCaptchaSettings - Moves captcha config out of the Comments
 * namespace and into the site-wide Captcha namespace.
 *
 * Captcha settings moved from Comments.captcha_* (owned by this plugin)
 * to Captcha.provider / Captcha.site_key / Captcha.secret_key (owned by
 * the core CaptchaService, edited under Settings > Captcha). This
 * migration copies any existing values across, then drops the old rows.
 *
 * The copy is skipped per key when a Captcha.* row already exists, so
 * re-running or re-installing never overwrites newer site-wide values.
 * down() is a no-op: the old keys are gone by design and restoring them
 * would only resurrect stale configuration.
 */
class MigrateCaptchaSettings extends Migration
{
    private const OLD_KEYS = [
        'Comments.captcha_provider' => 'Captcha.provider',
        'Comments.captcha_site_key' => 'Captcha.site_key',
        'Comments.captcha_secret_key' => 'Captcha.secret_key',
    ];

    public function up(): void
    {
        foreach (self::OLD_KEYS as $oldKey => $newKey) {
            $existing = (new Setting($this->connection()))->findByKey($newKey);
            if ($existing !== null) {
                continue;
            }

            $old = (new Setting($this->connection()))->findByKey($oldKey);
            if ($old === null) {
                continue;
            }

            $copy = new Setting($this->connection());
            $copy->key       = $newKey;
            $copy->value     = $old->value;
            $copy->type      = 'string';
            $copy->autoload  = true;
            $copy->insert();
        }

        foreach (array_keys(self::OLD_KEYS) as $oldKey) {
            $stale = (new Setting($this->connection()))->findByKey($oldKey);
            if ($stale !== null) {
                $stale->delete();
            }
        }
    }

    public function down(): void
    {
        // No-op. See the class docblock.
    }

    /**
     * The database connection the migrations runner boots the app with.
     */
    private function connection(): \PDO
    {
        return \Flight::db();
    }
}
