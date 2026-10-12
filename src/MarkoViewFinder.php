<?php

declare(strict_types=1);

namespace Marko\View\Blade;

use Illuminate\View\ViewFinderInterface;
use Marko\View\Exceptions\TemplateNotFoundException;
use Marko\View\Exceptions\ViewException;
use Marko\View\TemplateResolverInterface;

readonly class MarkoViewFinder implements ViewFinderInterface
{
    public function __construct(
        private TemplateResolverInterface $resolver,
        private string                    $extension = '.blade.php',
        private ?string                   $globalPath = null,
    )
    {
    }

    public function find($view): string
    {
        $template = str_replace('.', '/', $view);

        try {
            return $this->resolver->resolve($template);
        } catch (TemplateNotFoundException $e) {
            if ($this->globalPath !== null && !str_contains($template, '::') && !str_contains($template, '..')) {
                $globalFile = $this->globalPath . '/' . $template . $this->extension;
                if (is_file($globalFile)) {
                    return $globalFile;
                }
            }

            throw new \InvalidArgumentException(
                "View [{$view}] not found.",
                0,
                $e,
            );
        } catch (ViewException $e) {
            throw new \InvalidArgumentException(
                "View [{$view}] is invalid: {$e->getMessage()}",
                0,
                $e,
            );
        }
    }

    public function addLocation($location): void
    {
        // Marko resolves paths via TemplateResolverInterface.
        // Additional locations are not supported in this driver.
    }

    public function addNamespace($namespace, $hints): void
    {
        // Marko uses module namespaces handled by TemplateResolverInterface.
    }

    public function prependNamespace($namespace, $hints): void
    {
        // Marko uses module namespaces handled by TemplateResolverInterface.
    }

    public function replaceNamespace($namespace, $hints): void
    {
        // Marko uses module namespaces handled by TemplateResolverInterface.
    }

    public function addExtension($extension): void
    {
        // Extensions are configured via ViewConfig.
    }

    public function flush(): void
    {
        // Marko resolves templates dynamically; no cache to flush.
    }

    public function getPaths(): array
    {
        return [];
    }

    public function getHints(): array
    {
        return [];
    }

    public function getExtensions(): array
    {
        return [$this->extension];
    }
}
