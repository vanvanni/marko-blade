<?php

declare(strict_types=1);

namespace Marko\View\Blade;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Marko\Core\Container\ContainerInterface;
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

    private function resolvePath(string $path): string
    {
        if ($this->basePath === null || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            return $path;
        }

        return $this->basePath . '/' . $path;
    }

    public function create(): Factory
    {
        $filesystem = new Filesystem();
        $container = new Container();
        Container::setInstance($container);
        $events = new Dispatcher($container);

        $cachePath = $this->resolvePath($this->viewConfig->cacheDirectory());
        $autoRefresh = $this->viewConfig->autoRefresh();

        $bladeCompiler = new BladeCompiler(
            $filesystem,
            $cachePath,
            basePath: '',
            shouldCache: true,
            compiledExtension: 'php',
            shouldCheckTimestamps: $autoRefresh,
        );

        $this->registerViteDirectives($bladeCompiler, $container);

        $engineResolver = new EngineResolver();
        $engineResolver->register('blade', fn() => new CompilerEngine($bladeCompiler, $filesystem));

        $globalViewsPath = $this->globalViewsPath ?? ($this->basePath !== null ? $this->basePath . '/resources/views' : null);
        $finder = new MarkoViewFinder($this->resolver, $this->viewConfig->extension(), $globalViewsPath);

        $factory = new Factory($engineResolver, $finder, $events);
        $factory->setContainer($container);

        $container->instance('view', $factory);
        $container->instance(Factory::class, $factory);

        return $factory;
    }

    private function registerViteDirectives(BladeCompiler $compiler, Container $container): void
    {
        if (!class_exists(\Marko\Vite\Vite::class) || $this->markoContainer === null) {
            return;
        }

        if (!$this->markoContainer->has(\Marko\Vite\Vite::class)) {
            return;
        }

        $vite = $this->markoContainer->get(\Marko\Vite\Vite::class);
        $container->instance(\Marko\Vite\Vite::class, $vite);

        $compiler->directive('viteHeadTags', function ($expression) {
            return "<?php echo \\Illuminate\\Container\\Container::getInstance()->make(\\Marko\\Vite\\Vite::class)->headTags{$expression}; ?>";
        });
    }
}
