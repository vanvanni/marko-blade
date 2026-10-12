<?php

declare(strict_types=1);

namespace Marko\View\Blade\Tests;

use Illuminate\View\Factory;
use Marko\Config\ConfigRepository;
use Marko\Core\Container\ContainerInterface;
use Marko\View\Blade\BladeCompilerFactory;
use Marko\View\Exceptions\TemplateNotFoundException;
use Marko\View\TemplateResolverInterface;
use Marko\View\ViewConfig;
use PHPUnit\Framework\TestCase;

abstract class BladeTestCase extends TestCase
{
    protected string $cacheDir;

    protected string $viewsDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sys_get_temp_dir() . '/marko-blade-test-cache-' . uniqid();
        $this->viewsDir = sys_get_temp_dir() . '/marko-blade-test-views-' . uniqid();

        mkdir($this->cacheDir, 0700, true);
        mkdir($this->viewsDir . '/resources/views/post', 0777, true);
        mkdir($this->viewsDir . '/resources/views/layouts', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->recursiveDelete($this->cacheDir);
        $this->recursiveDelete($this->viewsDir);

        parent::tearDown();
    }

    protected function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($dir);
    }

    protected function writeView(string $name, string $contents): void
    {
        $path = $this->viewsDir . '/resources/views/' . $name . '.blade.php';

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $contents);
    }

    protected function createFactory(
        bool $autoRefresh = true,
        ?ContainerInterface $markoContainer = null,
    ): Factory {
        $config = new ConfigRepository([
            'view' => [
                'cache_directory' => $this->cacheDir,
                'extension' => '.blade.php',
                'auto_refresh' => $autoRefresh,
            ],
        ]);

        $resolver = new class ($this->viewsDir) implements TemplateResolverInterface {
            public function __construct(private readonly string $viewsDir) {}

            public function resolve(string $template): string
            {
                $path = $this->pathFor($template);

                if (!file_exists($path)) {
                    throw TemplateNotFoundException::forTemplate($template, [$path]);
                }

                return $path;
            }

            public function getSearchedPaths(string $template): array
            {
                return [$this->pathFor($template)];
            }

            private function pathFor(string $template): string
            {
                // Strip module prefix (e.g., blog::) for test resolution
                $pathPart = str_contains($template, '::')
                    ? explode('::', $template, 2)[1]
                    : $template;

                return $this->viewsDir . '/resources/views/' . $pathPart . '.blade.php';
            }
        };

        return (new BladeCompilerFactory(new ViewConfig($config), $resolver, null, null, $markoContainer))->create();
    }
}
