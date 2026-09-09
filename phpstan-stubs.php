<?php

/**
 * PHPStan type stubs for Pubvana.
 *
 * PHPStan-only metadata; nothing here runs at runtime and nothing in
 * vendor/ is modified. IDEs are unaffected: they read Flight's own
 * docblock in vendor/, not this file.
 *
 * Flight's Engine resolves service facades via __call(). The names are
 * registered at runtime by Pubvana's app/config/services.php
 * ($app->map('name', ...)) and by the enlivenapp packages (register()/
 * map() in their Plugin.php). PHPStan cannot see dynamic registrations,
 * so the facade methods are declared here as @phpstan-method tags.
 *
 * Because this stub's class docblock replaces Flight's own Engine
 * docblock for PHPStan, the @phpstan-template and @phpstan-method tags
 * from vendor/flightphp/core/flight/Engine.php are carried forward
 * verbatim below, followed by the Pubvana facades. When upgrading
 * flightphp/core, diff this block against the new vendor docblock and
 * re-sync.
 *
 * Stub rules: errors inside stub files cannot be ignored, and every
 * class referenced from a stub must itself be declared in a stub, even
 * as an empty shell (shells merge with the real classes; PHPStan reads
 * PHPDocs from stubs and methods from the real sources). That is what
 * the shell declarations at the bottom are for. Plain @method tags are
 * intentionally not used; @phpstan-method is the PHPStan-facing form.
 *
 * When adding a facade: register it in app/config/services.php (or the
 * owning plugin's Plugin.php) first, then add the @phpstan-method line
 * here with the real return type, plus a shell for the return class if
 * one does not exist yet. Keep the lists in sync.
 *
 * PROJECT_ROOT (defined at runtime during boot) is declared in
 * phpstan-bootstrap.php, loaded via bootstrapFiles.
 */

namespace flight {
    /**
     * The Engine class contains the core functionality of the framework.
     * It is responsible for loading an HTTP request, running the assigned services,
     * and generating an HTTP response.
     *
     * @phpstan-template EngineTemplate of object
     *
     * Flight framework methods (carried from vendor Engine docblock).
     *
     * @phpstan-method void start()
     * @phpstan-method void stop()
     * @phpstan-method void halt(int $code = 200, string $message = '', bool $actuallyExit = true)
     * @phpstan-method \flight\core\EventDispatcher eventDispatcher()
     * @phpstan-method \flight\net\Route route(string $pattern, callable|string|array{0: class-string, 1: string} $callback, bool $pass_route = false, string $alias = '')
     * @phpstan-method void group(string $pattern, callable $callback, (class-string|callable|array{0: class-string, 1: string})[] $group_middlewares = [])
     * @phpstan-method \flight\net\Route post(string $pattern, callable|string|array{0: class-string, 1: string} $callback, bool $pass_route = false, string $alias = '')
     * @phpstan-method \flight\net\Route put(string $pattern, callable|string|array{0: class-string, 1: string} $callback, bool $pass_route = false, string $alias = '')
     * @phpstan-method \flight\net\Route patch(string $pattern, callable|string|array{0: class-string, 1: string} $callback, bool $pass_route = false, string $alias = '')
     * @phpstan-method \flight\net\Route delete(string $pattern, callable|string|array{0: class-string, 1: string} $callback, bool $pass_route = false, string $alias = '')
     * @phpstan-method void resource(string $pattern, class-string $controllerClass, array<string, string|array<string>> $methods = [])
     * @phpstan-method \flight\net\Router router()
     * @phpstan-method string getUrl(string $alias, array<string, mixed> $params = [])
     * @phpstan-method void before(string $name, \Closure(array<int, mixed> &$params, string &$output): (void|false) $callback)
     * @phpstan-method void after(string $name, \Closure(array<int, mixed> &$params, string &$output): (void|false) $callback)
     * @phpstan-method void set(string|iterable<string, mixed> $key, ?mixed $value = null)
     * @phpstan-method mixed get(?string $key)
     * @phpstan-method void render(string $file, ?array<string, mixed> $data = null, ?string $key = null)
     * @phpstan-method \flight\template\View view()
     * @phpstan-method void onEvent(string $event, callable $callback)
     * @phpstan-method void triggerEvent(string $event, ...$args)
     * @phpstan-method \flight\net\Request request()
     * @phpstan-method \flight\net\Response response()
     * @phpstan-method void error(\Throwable $e)
     * @phpstan-method void notFound()
     * @phpstan-method void methodNotFound(\flight\net\Route $route)
     * @phpstan-method void redirect(string $url, int $code = 303)
     * @phpstan-method void json(mixed $data, int $code = 200, bool $encode = true, string $charset = "utf8", int $encodeOption = 0, int $encodeDepth = 512)
     * @phpstan-method never jsonHalt(mixed $data, int $code = 200, bool $encode = true, string $charset = 'utf-8', int $option = 0)
     * @phpstan-method void jsonp(mixed $data, string $param = 'jsonp', int $code = 200, bool $encode = true, string $charset = "utf8", int $encodeOption = 0, int $encodeDepth = 512)
     * @phpstan-method void etag(string $id, string $type = 'strong')
     * @phpstan-method void lastModified(int $time)
     * @phpstan-method void download(string $filePath)
     * @phpstan-method void registerContainerHandler(\Psr\Container\ContainerInterface|callable(class-string<EngineTemplate> $id, array<int|string, mixed> $params): ?EngineTemplate $containerHandler)
     *
     * Pubvana service facades, registered in app/config/services.php and
     * enlivenapp package Plugin.php files.
     *
     * @phpstan-method \flight\database\PdoWrapper db()
     * @phpstan-method \Pubvana\Services\ExtensionRegistry adext()
     * @phpstan-method string slugify(string $text)
     * @phpstan-method \Pubvana\Services\ThemeService themes()
     * @phpstan-method \Pubvana\Services\RegionManager regions()
     * @phpstan-method \Pubvana\Services\NavigationService navigation()
     * @phpstan-method \Pubvana\Services\SettingsService settings()
     * @phpstan-method \Pubvana\Services\Mailer mailer()
     * @phpstan-method \Pubvana\Services\ContentService content()
     * @phpstan-method \Pubvana\Services\CronService cron()
     * @phpstan-method \Pubvana\Services\AssetService asset()
     * @phpstan-method \Pubvana\Services\CaptchaService captcha()
     * @phpstan-method \Pubvana\Services\PluginLoader pluginLoader()
     * @phpstan-method \Enlivenapp\FlightSessions\SessionManager session()
     * @phpstan-method \Enlivenapp\FlightShield\Auth auth()
     *
     * Plugin service facades, registered by each plugin's Plugin.php.
     *
     * @phpstan-method \Pubvana\Plugins\Pages\Services\PagesService pages()
     * @phpstan-method \Pubvana\Plugins\Docs\Services\DocsService docs()
     * @phpstan-method \Pubvana\Plugins\Media\Services\MediaService media()
     * @phpstan-method \Pubvana\Plugins\Seo\Services\SeoService seo()
     * @phpstan-method \Pubvana\Plugins\Seo\Services\SchemaService seoSchema()
     * @phpstan-method \Pubvana\Plugins\Seo\Services\SitemapService seoSitemap()
     * @phpstan-method \Pubvana\Plugins\Seo\Services\RobotsTxtService seoRobots()
     * @phpstan-method \Pubvana\Plugins\Seo\Services\LlmsTxtService seoLlmsTxt()
     * @phpstan-method \Pubvana\Plugins\Seo\Services\ContentAnalysisService seoAnalysis()
     * @phpstan-method \Pubvana\Plugins\Comments\Services\CommentService comments()
     * @phpstan-method \Pubvana\Plugins\Blog\Services\BlogService blog()
     * @phpstan-method \Pubvana\Plugins\Search\Services\SearchService search()
     * @phpstan-method \Pubvana\Plugins\Forms\Services\FormsService forms()
     * @phpstan-method \Pubvana\Plugins\Profiles\Models\Profile profiles()
     * @phpstan-method \Pubvana\Plugins\Redirects\Services\RedirectsService redirects()
     * @phpstan-method \Pubvana\Plugins\Redirects\Services\RedirectLinksService redirectLinks()
     * @phpstan-method \Pubvana\Plugins\Analytics\Services\AnalyticsService analytics()
     * @phpstan-method \Pubvana\Plugins\Backups\Services\BackupService backups()
     * @phpstan-method \Pubvana\Plugins\Import\Services\ImportService import()
     * @phpstan-method \Pubvana\Plugins\SiteHealth\Services\HealthService health()
     * @phpstan-method \Pubvana\Plugins\SocialLinks\Services\SocialLinksService socialLinks()
 * @phpstan-method \Pubvana\Plugins\AiAssistant\Services\AiService ai()
 * @phpstan-method \Pubvana\Plugins\AiAssistant\Services\FactCheckService aiFactCheck()
 * @phpstan-method \Pubvana\Plugins\AiAssistant\Services\MarkdownService aiMarkdown()
 * @phpstan-method \Pubvana\Plugins\ActivityLog\Services\ActivityLogService activityLog()
     * @phpstan-method \Pubvana\Plugins\BrokenLinks\Services\BrokenLinksService brokenLinks()
     * @phpstan-method \Pubvana\Plugins\Updates\Services\UpdateService updates()
     * @phpstan-method \Pubvana\Plugins\Marketplace\Services\MarketplaceService marketplace()
     */
    class Engine
    {
    }
}

namespace {
    /**
     * Static proxy to the app Engine instance. Used sparingly where the
     * app instance is not injectable (plugin static contexts, helpers).
     * Only the methods actually used across the codebase are declared;
     * add more here following the facade list on Engine.
     *
     * @phpstan-method static mixed get(?string $key)
     * @phpstan-method static \flight\Engine<object> app()
     * @phpstan-method static \flight\database\PdoWrapper db()
     * @phpstan-method static string slugify(string $text)
     * @phpstan-method static \Pubvana\Services\Mailer mailer()
     */
    class Flight
    {
    }
}

namespace flight\database {
    class PdoWrapper
    {
    }
}

namespace flight\net {
    class Route
    {
    }

    class Router
    {
    }

    class Request
    {
    }

    class Response
    {
    }
}

namespace flight\template {
    class View
    {
    }
}

namespace flight\core {
    class EventDispatcher
    {
    }
}

namespace Psr\Container {
    interface ContainerInterface
    {
    }
}

namespace Pubvana\Services {
    class ExtensionRegistry
    {
    }

    class ThemeService
    {
    }

    class RegionManager
    {
    }

    class NavigationService
    {
    }

    class SettingsService
    {
    }

    class Mailer
    {
    }

    class ContentService
    {
    }

    class CronService
    {
    }

    class AssetService
    {
    }

    class CaptchaService
    {
    }

    class CaptchaMiddleware
    {
    }

    class PluginLoader
    {
    }
}

namespace Enlivenapp\FlightSessions {
    class SessionManager
    {
    }
}

namespace Enlivenapp\FlightShield {
    class Auth
    {
    }
}

namespace Ahc\Cli\IO {
    /**
     * Interactive IO helper. The output convenience methods are resolved
     * through __call() to the Writer, so they are declared here.
     *
     * @method void write(string $text, bool $eol = false)
     * @method void error(string $text, bool $eol = false)
     * @method void info(string $text, bool $eol = false)
     * @method void ok(string $text, bool $eol = false)
     * @method void comment(string $text, bool $eol = false)
     */
    class Interactor
    {
    }
}

namespace Pubvana\Plugins\Pages\Services {
    class PagesService
    {
    }
}

namespace Pubvana\Plugins\Docs\Services {
    class DocsService
    {
    }
}

namespace Pubvana\Plugins\Media\Services {
    class MediaService
    {
    }
}

namespace Pubvana\Plugins\Seo\Services {
    class SeoService
    {
    }

    class SchemaService
    {
    }

    class SitemapService
    {
    }

    class RobotsTxtService
    {
    }

    class LlmsTxtService
    {
    }

    class ContentAnalysisService
    {
    }
}

namespace Pubvana\Plugins\Comments\Services {
    class CommentService
    {
    }
}

namespace Pubvana\Plugins\Blog\Services {
    class BlogService
    {
    }
}

namespace Pubvana\Plugins\Search\Services {
    class SearchService
    {
    }
}

namespace Pubvana\Plugins\Forms\Services {
    class FormsService
    {
    }
}

namespace Pubvana\Plugins\Profiles\Models {
    class Profile
    {
    }
}

namespace Pubvana\Plugins\Redirects\Services {
    class RedirectsService
    {
    }

    class RedirectLinksService
    {
    }
}

namespace Pubvana\Plugins\Analytics\Services {
    class AnalyticsService
    {
    }
}

namespace Pubvana\Plugins\Backups\Services {
    class BackupService
    {
    }
}

namespace Pubvana\Plugins\SiteHealth\Services {
    class HealthService
    {
    }
}

namespace Pubvana\Plugins\SocialLinks\Services {
    class SocialLinksService
    {
    }
}

namespace Pubvana\Plugins\AiAssistant\Services {
    class AiService
    {
    }

    class FactCheckService
    {
    }

    class MarkdownService
    {
    }
}

namespace Pubvana\Plugins\ActivityLog\Services {
    class ActivityLogService
    {
    }
}

namespace Pubvana\Plugins\BrokenLinks\Services {
    class BrokenLinksService
    {
    }
}

namespace Pubvana\Plugins\Updates\Services {
    class UpdateService
    {
    }
}

namespace Pubvana\Plugins\Marketplace\Services {
    class MarketplaceService
    {
    }
}

namespace Enlivenapp\FlightShield\Models {
    /**
     * Vendor User model. `find()` is declared on the ActiveRecord base returning
     * the base type; re-declare it here so callers see the concrete User and its
     * real properties (username, id, ...).
     *
     * @phpstan-method self|null find($id = null)
     */
    class User
    {
    }
}

namespace Pubvana\Plugins\Import\Services {
    class ImportService
    {
    }
}
