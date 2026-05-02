<?php

declare(strict_types=1);

use Marko\Core\Container\ContainerInterface;
use Marko\View\Blade\BladeCompilerFactory;
use Marko\View\Blade\BladeView;
use Marko\View\ViewInterface;

return [
    'bindings' => [
        BladeCompilerFactory::class => function (ContainerInterface $container): BladeCompilerFactory {
            $viewConfig = $container->get(\Marko\View\ViewConfig::class);
            $resolver = $container->get(\Marko\View\TemplateResolverInterface::class);

            $basePath = null;
            if ($container->has(\Marko\Core\Path\ProjectPaths::class)) {
                $basePath = $container->get(\Marko\Core\Path\ProjectPaths::class)->base;
            }

            return new BladeCompilerFactory($viewConfig, $resolver, null, $basePath, $container);
        },
        ViewInterface::class => function (ContainerInterface $container): ViewInterface {
            $factory = $container->get(BladeCompilerFactory::class)->create();

            return new BladeView($factory);
        },
    ],
];
