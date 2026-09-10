<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant;

use Enlivenapp\FlightShield\Middlewares\PermissionMiddleware;
use Pubvana\Plugins\AiAssistant\Controllers\AiAdminController;
use Pubvana\Plugins\AiAssistant\Controllers\AiApiController;
use Pubvana\Plugins\AiAssistant\Controllers\AiFactCheckAdminController;
use Pubvana\Plugins\AiAssistant\Services\AiService;
use Pubvana\Plugins\AiAssistant\Services\FactCheckService;
use Pubvana\Plugins\AiAssistant\Services\MarkdownService;
use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

/**
 * AI Assistant Plugin - API-key ingestion endpoints and per-key grants.
 *
 * Registers the `ai`, `aiFactCheck`, and `aiMarkdown` services, the
 * admin screens under Tools > AI Assistant, and the sessionless `/api/ai/*`
 * REST API. Every public endpoint authenticates with a per-request
 * bearer API key; grants are deny-all until an admin explicitly grants a
 * permission to a key. The plugin registers its `/api/ai/*` prefix under the
 * csrf.exempt extension type so the core CSRF middleware skips these
 * sessionless endpoints (they cannot present a session token).
 *
 * Fact Checking is the one site-level feature: instead of per-key
 * grants, its endpoints open when the admin accepts the prompt's terms
 * and flips the toggle, and stay shut otherwise.
 *
 * @package Pubvana\Plugins\AiAssistant
 */
class Plugin implements PluginInterface
{
    public function register(Engine $app, Router $router, array $config = []): void
    {
        $prefix = $app->pluginLoader()->routePrefix('pubvana/ai');
        $config['route_prefix'] = $prefix;
        $apiPrefix = $app->pluginLoader()->apiPrefix('pubvana/ai');

        $app->map('ai', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new AiService($app->db(), $app, $config);
            }
            return $instance;
        });

        $app->map('aiFactCheck', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new FactCheckService($app->db(), $app, $config);
            }
            return $instance;
        });

        $app->map('aiMarkdown', function () use ($config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new MarkdownService($config);
            }
            return $instance;
        });

        $adext = $app->adext();

        // Exempt the sessionless /api/ai/* API from CSRF validation.
        $adext->register('csrf.exempt', 'default', 'pubvana.ai', [
            'prefix' => $apiPrefix . '/',
            'label'  => 'AI Assistant API',
        ]);

        // Admin screens require the seeded ai.manage permission.
        $manageMiddleware = new PermissionMiddleware($app, 'ai.manage');

        // ─── Admin Routes (adext prepends /admin) ──────────────────────

        $adext->addRoutes('admin', [
            ['GET',  $prefix . '/manage',                    [AiAdminController::class, 'manage'],       [$manageMiddleware]],
            ['POST', $prefix . '/manage/keys',               [AiAdminController::class, 'createKey'],    [$manageMiddleware]],
            ['POST', $prefix . '/manage/keys/@id/grants',    [AiAdminController::class, 'updateGrants'], [$manageMiddleware]],
            ['POST', $prefix . '/manage/keys/@id/toggle',    [AiAdminController::class, 'toggleKey'],    [$manageMiddleware]],
            ['POST', $prefix . '/manage/keys/@id/delete',    [AiAdminController::class, 'deleteKey'],    [$manageMiddleware]],
            ['POST', $prefix . '/manage/author',             [AiAdminController::class, 'saveAuthor'],   [$manageMiddleware]],
            ['GET',  $prefix . '/help',                      [AiAdminController::class, 'help'],         [$manageMiddleware]],
            ['GET',  $prefix . '/fact-checks',               [AiFactCheckAdminController::class, 'index'],       [$manageMiddleware]],
            ['GET',  $prefix . '/fact-checks/@id',           [AiFactCheckAdminController::class, 'show'],        [$manageMiddleware]],
            ['POST', $prefix . '/fact-checks/terms',         [AiFactCheckAdminController::class, 'acceptTerms'], [$manageMiddleware]],
            ['POST', $prefix . '/fact-checks/toggle',        [AiFactCheckAdminController::class, 'toggle'],      [$manageMiddleware]],
            ['POST', $prefix . '/fact-checks/@id/delete',    [AiFactCheckAdminController::class, 'delete'],      [$manageMiddleware]],
        ], 'pubvana.ai');

        // ─── Public REST API (sessionless, bearer-key auth) ─────────────
        // Static taxonomy routes (/posts/tags, /posts/categories) MUST be
        // registered before the parameterized /posts/@slug route.

        $adext->addRoutes('public', [
            ['GET',  $apiPrefix . '/help',                        [AiApiController::class, 'help']],
            ['GET',  $apiPrefix . '/help/@permission',             [AiApiController::class, 'helpPermission']],
            ['GET',  $apiPrefix . '/posts',                        [AiApiController::class, 'posts']],
            ['GET',  $apiPrefix . '/posts/tags',                   [AiApiController::class, 'tags']],
            ['GET',  $apiPrefix . '/posts/categories',             [AiApiController::class, 'categories']],
            ['GET',  $apiPrefix . '/posts/@slug',                  [AiApiController::class, 'post']],
            ['POST', $apiPrefix . '/posts',                        [AiApiController::class, 'createPost']],
            ['POST', $apiPrefix . '/posts/@id/update',             [AiApiController::class, 'updatePost']],
            ['POST', $apiPrefix . '/posts/@id/delete',             [AiApiController::class, 'deletePost']],
            ['GET',  $apiPrefix . '/pages',                        [AiApiController::class, 'pages']],
            ['GET',  $apiPrefix . '/pages/@slug',                  [AiApiController::class, 'page']],
            ['POST', $apiPrefix . '/pages',                        [AiApiController::class, 'createPage']],
            ['POST', $apiPrefix . '/pages/@id/update',             [AiApiController::class, 'updatePage']],
            ['POST', $apiPrefix . '/pages/@id/delete',             [AiApiController::class, 'deletePage']],
            ['GET',  $apiPrefix . '/comments',                     [AiApiController::class, 'comments']],
            ['POST', $apiPrefix . '/comments/@id/approve',         [AiApiController::class, 'approveComment']],
            ['POST', $apiPrefix . '/comments/@id/reject',          [AiApiController::class, 'rejectComment']],
            ['POST', $apiPrefix . '/comments/@id/delete',          [AiApiController::class, 'deleteComment']],
            ['GET',  $apiPrefix . '/redirects',                    [AiApiController::class, 'redirects']],
            ['POST', $apiPrefix . '/redirects',                    [AiApiController::class, 'createRedirect']],
            ['POST', $apiPrefix . '/redirects/@id/update',         [AiApiController::class, 'updateRedirect']],
            ['POST', $apiPrefix . '/redirects/@id/delete',         [AiApiController::class, 'deleteRedirect']],
            ['GET',  $apiPrefix . '/navigation',                   [AiApiController::class, 'navigation']],
            ['POST', $apiPrefix . '/navigation',                   [AiApiController::class, 'createNavigation']],
            ['POST', $apiPrefix . '/navigation/@id/update',        [AiApiController::class, 'updateNavigation']],
            ['POST', $apiPrefix . '/navigation/@id/delete',        [AiApiController::class, 'deleteNavigation']],
            ['GET',  $apiPrefix . '/fact-check/prompt',            [AiApiController::class, 'factCheckPrompt']],
            ['GET',  $apiPrefix . '/fact-checks',                  [AiApiController::class, 'factChecks']],
            ['GET',  $apiPrefix . '/fact-checks/@id',              [AiApiController::class, 'factCheck']],
            ['POST', $apiPrefix . '/posts/@id/fact-check',         [AiApiController::class, 'submitPostFactCheck']],
            ['POST', $apiPrefix . '/pages/@id/fact-check',         [AiApiController::class, 'submitPageFactCheck']],
            ['GET',  $apiPrefix . '/broken-links',                 [AiApiController::class, 'brokenLinks']],
            ['GET',  $apiPrefix . '/analytics',                    [AiApiController::class, 'analytics']],
        ], 'pubvana.ai');

        // ─── Content Edit Panel (read-only, in Blog/Pages editors) ─────

        $adext->register('content.edit.panel', 'default', 'pubvana.ai.factcheck', [
            'label'    => 'Fact Check',
            'priority' => 60,
            'callable' => function (array $context) use ($app, $prefix): string {
                $contentType = ($context['content_type'] ?? '') === 'page' ? 'page' : 'post';
                $contentId = (int) ($context['content_id'] ?? 0);

                return $app->view()->fetch('pubvana/ai/admin/fact-check-panel', [
                    'panel'       => $app->aiFactCheck()->panelData($contentType, $contentId),
                    'content_id'  => $contentId,
                    'adminBase'   => '/admin' . rtrim($prefix, '/'),
                ]);
            },
        ]);

        // ─── Public Block: Fact Check Summary ──────────────────────────

        $adext->register('block', 'available', 'pubvana.ai.fact-check-summary', [
            'label'       => 'Fact Check Summary',
            'description' => 'Shows the fact-check findings and verdict for the post or page being viewed. Renders nothing where no report exists.',
            'provider'    => fn (array $options) => $app->aiFactCheck()->blockData($options),
            'template'    => 'pubvana/ai/public/blocks/fact-check-summary',
            'priority'    => 60,
            'options'     => [
                'title' => ['type' => 'input', 'label' => 'Title', 'default' => 'Fact Check'],
            ],
        ]);
    }
}