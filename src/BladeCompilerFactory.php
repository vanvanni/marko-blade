<?php

declare(strict_types=1);

namespace Marko\View\Blade;

use Illuminate\Container\Container;
use Illuminate\Contracts\View\Factory as FactoryContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Component;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Marko\Config\ConfigRepositoryInterface;
use Marko\Core\Container\ContainerInterface;
use Marko\Core\Path\ProjectPaths;
use Marko\Routing\UrlGeneratorInterface;
use Marko\View\CacheDirectoryGuard;
use Marko\View\TemplateResolverInterface;
use Marko\View\ViewConfig;

readonly class BladeCompilerFactory
{
    public function __construct(
        private ViewConfig                $viewConfig,
        private TemplateResolverInterface $resolver,
        private ?string                   $globalViewsPath = null,
        private ?string                   $basePath = null,
        private ?ContainerInterface       $markoContainer = null,
    )
    {
    }

    public function create(): Factory
    {
        $filesystem = new Filesystem();
        $container = new Container();
        Container::setInstance($container);
        // Components cache the first factory they see in a static; reset it so components use this one.
        Component::forgetFactory();
        Component::flushCache();
        $events = new Dispatcher($container);

        $bladeCompiler = new MarkoBladeCompiler(
            $filesystem,
            $this->cacheDirectory(),
            basePath: '',
            shouldCache: true,
            compiledExtension: 'php',
            shouldCheckTimestamps: $this->viewConfig->autoRefresh(),
        );

        $this->registerMarkoServices($bladeCompiler, $container);

        $engineResolver = new EngineResolver();
        $engineResolver->register('blade', fn() => new CompilerEngine($bladeCompiler, $filesystem));

        $globalViewsPath = $this->globalViewsPath ?? ($this->basePath !== null ? $this->basePath . '/resources/views' : null);
        $finder = new MarkoViewFinder($this->resolver, $this->viewConfig->extension(), $globalViewsPath);

        $factory = new Factory($engineResolver, $finder, $events);
        $factory->setContainer($container);

        $container->instance('view', $factory);
        $container->instance(Factory::class, $factory);
        $container->instance(FactoryContract::class, $factory);

        return $factory;
    }

    private function cacheDirectory(): string
    {
        $directory = $this->viewConfig->cacheDirectory();
        $paths = new ProjectPaths($this->basePath);

        if (PHP_OS_FAMILY !== 'Windows') {
            return (new CacheDirectoryGuard($paths))->prepare($directory);
        }

        // shortcut: Windows skips CacheDirectoryGuard's permission check because PHP reports every directory as 0777, upgrade when marko/view supports Windows.
        return preg_match('/^[a-z]:[\\\\\/]/i', $directory) === 1 ? $directory : $paths->base . '/' . $directory;
    }

    private function registerMarkoServices(BladeCompiler $compiler, Container $container): void
    {
        $this->registerViteDirectives($compiler);
        $this->registerEnvironmentDirectives($compiler);

        if ($this->markoContainer === null) {
            return;
        }

        $markoContainer = $this->markoContainer;

        $container->bind(ConfigRepositoryInterface::class, fn () => $markoContainer->get(ConfigRepositoryInterface::class));

        if ($markoContainer->has(UrlGeneratorInterface::class)) {
            $container->bind(UrlGeneratorInterface::class, fn () => $markoContainer->get(UrlGeneratorInterface::class));
        }

        if (class_exists(\Marko\Vite\Vite::class) && $markoContainer->has(\Marko\Vite\Vite::class)) {
            $container->bind(\Marko\Vite\Vite::class, fn () => $markoContainer->get(\Marko\Vite\Vite::class));
        }
    }

    private function registerViteDirectives(BladeCompiler $compiler): void
    {
        $tags = static function (string $expression): string {
            $entries = $expression === '' ? '[null]' : "(array) ({$expression})";

            return '<?php echo implode("\n", array_map(fn ($entry) => \Illuminate\Container\Container::getInstance()->make(\Marko\Vite\Vite::class)->headTags($entry), ' . $entries . ')); ?>';
        };

        $compiler->directive('vite', $tags);
        $compiler->directive('viteHeadTags', $tags);
        // Marko's headTags() already emits the React Refresh preamble for .jsx/.tsx entries.
        $compiler->directive('viteReactRefresh', static fn (): string => '');
    }

    private function registerEnvironmentDirectives(BladeCompiler $compiler): void
    {
        $compiler->directive('env', static fn (string $expression): string => "<?php if (in_array(\Marko\Config\Env::string('APP_ENV', 'local'), \Illuminate\Support\Arr::flatten([{$expression}]), true)): ?>");
        $compiler->directive('production', static fn (): string => "<?php if (\Marko\Config\Env::string('APP_ENV', 'local') === 'production'): ?>");
    }
}
