<?php

declare(strict_types=1);

use Marko\Core\Container\ContainerInterface;
use Marko\View\Blade\BladeCompilerFactory;
use Marko\View\Blade\BladeView;
use Marko\View\ViewInterface;

return [
    'bindings' => [
        ViewInterface::class => function (ContainerInterface $container): ViewInterface {
            $factory = $container->get(BladeCompilerFactory::class)->create();

            return new BladeView($factory);
        },
    ],
];
