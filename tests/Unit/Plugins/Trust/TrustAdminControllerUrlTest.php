<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Trust;

use Pubvana\Plugins\Trust\Controllers\TrustAdminController;
use Pubvana\Tests\Support\TestCase;

/**
 * TrustAdminController URL base tests.
 *
 * The admin URL base must be /admin + the resolved route prefix, so
 * changing routePrepend changes every admin redirect and view link.
 */
final class TrustAdminControllerUrlTest extends TestCase
{
    public function testAdminBaseDerivesFromRoutePrefix(): void
    {
        $app = $this->app([
            'pluginLoader' => fn () => new class {
                public function routePrefix(string $plugin): string
                {
                    return $plugin === 'pubvana/trust' ? '/trust' : '/other';
                }
            },
        ]);

        $controller = new TrustAdminController($app);

        self::assertSame('/admin/trust', $this->invoke($controller, 'adminBase'));
    }

    public function testAdminBaseFollowsCustomPrepend(): void
    {
        $app = $this->app([
            'pluginLoader' => fn () => new class {
                public function routePrefix(string $plugin): string
                {
                    return '/vetting';
                }
            },
        ]);

        $controller = new TrustAdminController($app);

        self::assertSame('/admin/vetting', $this->invoke($controller, 'adminBase'));
    }
}