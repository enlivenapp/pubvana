<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Profiles;

use Pubvana\Plugins\Profiles\Controllers\ProfilesAdminController;
use Pubvana\Tests\Support\TestCase;

/**
 * ProfilesAdminController URL base tests.
 *
 * The admin URL base must be /admin + the resolved route prefix, so
 * changing routePrepend changes every admin redirect and view link.
 */
final class ProfilesAdminControllerUrlTest extends TestCase
{
    public function testAdminBaseDerivesFromRoutePrefix(): void
    {
        $app = $this->app([
            'pluginLoader' => fn () => new class {
                public function routePrefix(string $plugin): string
                {
                    return $plugin === 'pubvana/profiles' ? '/profile' : '/other';
                }
            },
        ]);

        $controller = new ProfilesAdminController($app);

        self::assertSame('/admin/profile', $this->invoke($controller, 'adminBase'));
    }

    public function testAdminBaseFollowsCustomPrepend(): void
    {
        $app = $this->app([
            'pluginLoader' => fn () => new class {
                public function routePrefix(string $plugin): string
                {
                    return '/people';
                }
            },
        ]);

        $controller = new ProfilesAdminController($app);

        self::assertSame('/admin/people', $this->invoke($controller, 'adminBase'));
    }
}
