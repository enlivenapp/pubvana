<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Comments;

use Pubvana\Plugins\Comments\Controllers\CommentsAdminController;
use Pubvana\Tests\Support\TestCase;

/**
 * CommentsAdminController URL base tests.
 *
 * The admin URL base must be /admin + the resolved route prefix, so
 * changing routePrepend changes every admin redirect and view link.
 */
final class CommentsAdminControllerUrlTest extends TestCase
{
    public function testAdminBaseDerivesFromRoutePrefix(): void
    {
        $app = $this->app([
            'pluginLoader' => fn () => new class {
                public function routePrefix(string $plugin): string
                {
                    return $plugin === 'pubvana/comments' ? '/comments' : '/other';
                }
            },
        ]);

        $controller = new CommentsAdminController($app);

        self::assertSame('/admin/comments', $this->invoke($controller, 'adminBase'));
    }

    public function testAdminBaseFollowsCustomPrepend(): void
    {
        $app = $this->app([
            'pluginLoader' => fn () => new class {
                public function routePrefix(string $plugin): string
                {
                    return '/feedback';
                }
            },
        ]);

        $controller = new CommentsAdminController($app);

        self::assertSame('/admin/feedback', $this->invoke($controller, 'adminBase'));
    }
}
