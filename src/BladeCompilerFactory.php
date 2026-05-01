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
use Marko\View\TemplateResolverInterface;
use Marko\View\ViewConfig;

readonly class BladeCompilerFactory
{
    public function __construct(
        private ViewConfig                $viewConfig,
        private TemplateResolverInterface $resolver,
        private ?string                   $globalViewsPath = null,
    )
    {
    }

    public function create(): Factory
    {
        $filesystem = new Filesystem();
        $container = new Container();
        $events = new Dispatcher($container);

        $cachePath = $this->viewConfig->cacheDirectory();
        $autoRefresh = $this->viewConfig->autoRefresh();

        $bladeCompiler = new BladeCompiler(
            $filesystem,
            $cachePath,
            basePath: '',
            shouldCache: true,
            compiledExtension: 'php',
            shouldCheckTimestamps: $autoRefresh,
        );

        $engineResolver = new EngineResolver();
        $engineResolver->register('blade', fn() => new CompilerEngine($bladeCompiler, $filesystem));

        $finder = new MarkoViewFinder($this->resolver, $this->viewConfig->extension(), $this->globalViewsPath);

        $factory = new Factory($engineResolver, $finder, $events);
        $factory->setContainer($container);

        $container->instance('view', $factory);
        $container->instance(Factory::class, $factory);

        return $factory;
    }
}
