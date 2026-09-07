<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit;

use Pubvana\Controllers\Public\PublicController;
use Pubvana\Tests\Support\TestCase;

/**
 * PublicController flash collection tests.
 *
 * buildFlash() reads the standard session type keys and shapes them for
 * the theme templates: 'error' normalizes to 'danger', and anything not
 * a non-empty string is dropped.
 */
final class PublicControllerFlashTest extends TestCase
{
    public function testNoFlashYieldsEmptyList(): void
    {
        $app = $this->app(['session' => fn () => new FakeSession([])]);

        $controller = new class ($app) extends PublicController {
        };

        self::assertSame([], $this->invoke($controller, 'buildFlash'));
    }

    public function testDangerFlashIsCollected(): void
    {
        $app = $this->app(['session' => fn () => new FakeSession([
            'danger' => 'You can only edit your own profile.',
        ])]);

        $controller = new class ($app) extends PublicController {
        };

        self::assertSame([
            ['type' => 'danger', 'message' => 'You can only edit your own profile.'],
        ], $this->invoke($controller, 'buildFlash'));
    }

    public function testErrorNormalizesToDanger(): void
    {
        $app = $this->app(['session' => fn () => new FakeSession([
            'error' => 'Something went wrong.',
        ])]);

        $controller = new class ($app) extends PublicController {
        };

        self::assertSame([
            ['type' => 'danger', 'message' => 'Something went wrong.'],
        ], $this->invoke($controller, 'buildFlash'));
    }

    public function testTypeOrderIsPreserved(): void
    {
        $app = $this->app(['session' => fn () => new FakeSession([
            'success' => 'Saved.',
            'warning' => 'Careful.',
        ])]);

        $controller = new class ($app) extends PublicController {
        };

        self::assertSame([
            ['type' => 'success', 'message' => 'Saved.'],
            ['type' => 'warning', 'message' => 'Careful.'],
        ], $this->invoke($controller, 'buildFlash'));
    }

    public function testNonStringAndEmptyMessagesAreDropped(): void
    {
        $app = $this->app(['session' => fn () => new FakeSession([
            'info'   => '',
            'danger' => ['not', 'a', 'string'],
            'error'  => 'Kept.',
        ])]);

        $controller = new class ($app) extends PublicController {
        };

        self::assertSame([
            ['type' => 'danger', 'message' => 'Kept.'],
        ], $this->invoke($controller, 'buildFlash'));
    }
}

/**
 * Minimal session stand-in exposing just the flash API buildFlash() uses.
 */
final class FakeSession
{
    /** @var array<string, mixed> */
    private array $storage;

    /**
     * @param array<string, mixed> $storage
     */
    public function __construct(array $storage)
    {
        $this->storage = $storage;
    }

    public function hasFlash(string $key): bool
    {
        return array_key_exists($key, $this->storage);
    }

    public function pullFlash(string $key, mixed $default = null): mixed
    {
        $value = $this->storage[$key] ?? $default;
        unset($this->storage[$key]);

        return $value;
    }
}