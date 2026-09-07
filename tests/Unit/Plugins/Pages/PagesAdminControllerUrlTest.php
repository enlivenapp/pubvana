<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Pages;

use Pubvana\Plugins\Pages\Controllers\PagesAdminController;
use Pubvana\Tests\Support\TestCase;

/**
 * PagesAdminController URL base tests.
 *
 * The admin URL base must be /admin + the resolved route prefix, so
 * changing routePrepend changes every admin redirect and view link.
 */
final class PagesAdminControllerUrlTest extends TestCase
{
    public function testAdminBaseDerivesFromRoutePrefix(): void
    {
        $app = $this->app([
            'pluginLoader' => fn () => new class {
                public function routePrefix(string $plugin): string
                {
                    return $plugin === 'pubvana/pages' ? '/page' : '/other';
                }
            },
        ]);

        $controller = new PagesAdminController($app);

        self::assertSame('/admin/page', $this->invoke($controller, 'adminBase'));
    }

    public function testAdminBaseFollowsCustomPrepend(): void
    {
        $app = $this->app([
            'pluginLoader' => fn () => new class {
                public function routePrefix(string $plugin): string
                {
                    return '/docs';
                }
            },
        ]);

        $controller = new PagesAdminController($app);

        self::assertSame('/admin/docs', $this->invoke($controller, 'adminBase'));
    }
}
