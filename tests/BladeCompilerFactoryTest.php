<?php

declare(strict_types=1);

namespace Marko\View\Blade\Tests;

use Marko\Config\ConfigRepositoryInterface;
use Marko\View\Blade\BladeCompilerFactory;
use Marko\View\TemplateResolverInterface;
use Marko\View\ViewConfig;
use PHPUnit\Framework\TestCase;

class BladeCompilerFactoryTest extends TestCase
{
    private string $cacheDir;
    private string $viewsDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sys_get_temp_dir() . '/marko-blade-test-cache-' . uniqid();
        $this->viewsDir = sys_get_temp_dir() . '/marko-blade-test-views-' . uniqid();

        mkdir($this->cacheDir, 0777, true);
        mkdir($this->viewsDir . '/resources/views/post', 0777, true);
        mkdir($this->viewsDir . '/resources/views/layouts', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->recursiveDelete($this->cacheDir);
        $this->recursiveDelete($this->viewsDir);

        parent::tearDown();
    }

    private function recursiveDelete(string $dir): void
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

    public function testRendersSimpleBladeTemplate(): void
    {
        file_put_contents(
            $this->viewsDir . '/resources/views/post/show.blade.php',
            '<h1>{{ $title }}</h1>',
        );

        $factory = $this->createFactory();
        $html = $factory->make('blog::post/show', ['title' => 'Hello Blade'])->render();

        $this->assertSame('<h1>Hello Blade</h1>', trim($html));
    }

    public function testRendersBladeWithIncludes(): void
    {
        file_put_contents(
            $this->viewsDir . '/resources/views/layouts/app.blade.php',
            '<html><body>@yield(\'content\')</body></html>',
        );

        file_put_contents(
            $this->viewsDir . '/resources/views/post/index.blade.php',
            "@extends('blog::layouts.app')\n@section('content')\n<ul>@foreach(\$posts as \$post)<li>{{ \$post }}</li>@endforeach</ul>\n@endsection",
        );

        $factory = $this->createFactory();
        $html = $factory->make('blog::post/index', ['posts' => ['A', 'B']])->render();

        $this->assertStringContainsString('<li>A</li>', $html);
        $this->assertStringContainsString('<li>B</li>', $html);
        $this->assertStringContainsString('<html>', $html);
    }

    public function testCachesCompiledTemplateWhenAutoRefreshDisabled(): void
    {
        file_put_contents(
            $this->viewsDir . '/resources/views/post/show.blade.php',
            '<h1>{{ $title }}</h1>',
        );

        $factory = $this->createFactory(autoRefresh: false);
        $html1 = $factory->make('blog::post/show', ['title' => 'First'])->render();

        // Modify the source file
        file_put_contents(
            $this->viewsDir . '/resources/views/post/show.blade.php',
            '<h2>{{ $title }}</h2>',
        );

        // With autoRefresh disabled, it should still serve the cached version
        $html2 = $factory->make('blog::post/show', ['title' => 'Second'])->render();

        $this->assertSame('<h1>Second</h1>', trim($html2));
    }

    public function testRecompilesWhenAutoRefreshEnabled(): void
    {
        file_put_contents(
            $this->viewsDir . '/resources/views/post/show.blade.php',
            '<h1>{{ $title }}</h1>',
        );

        $factory = $this->createFactory(autoRefresh: true);
        $html1 = $factory->make('blog::post/show', ['title' => 'First'])->render();

        // Modify the source file (sleep to ensure mtime changes)
        sleep(1);
        file_put_contents(
            $this->viewsDir . '/resources/views/post/show.blade.php',
            '<h2>{{ $title }}</h2>',
        );

        clearstatcache(true, $this->viewsDir . '/resources/views/post/show.blade.php');

        // Simulate a new request by creating a fresh factory.
        // Blade's CompilerEngine caches compiled status per instance,
        // so a new factory is needed to verify auto-refresh behavior.
        $factory2 = $this->createFactory(autoRefresh: true);
        $html2 = $factory2->make('blog::post/show', ['title' => 'Second'])->render();

        $this->assertSame('<h2>Second</h2>', trim($html2));
    }

    private function createFactory(bool $autoRefresh = true): \Illuminate\View\Factory
    {
        $config = $this->createMock(ConfigRepositoryInterface::class);
        $config->method('getString')
            ->willReturnCallback(fn (string $key) => match ($key) {
                'view.cache_directory' => $this->cacheDir,
                'view.extension' => '.blade.php',
                default => '',
            });
        $config->method('getBool')
            ->willReturnCallback(fn (string $key) => match ($key) {
                'view.auto_refresh' => $autoRefresh,
                'view.strict_types' => false,
                default => false,
            });

        $viewConfig = new ViewConfig($config);

        $resolver = $this->createMock(TemplateResolverInterface::class);
        $resolver->method('resolve')
            ->willReturnCallback(function (string $template) {
                // Strip module prefix (e.g., blog::) for test resolution
                $pathPart = str_contains($template, '::')
                    ? explode('::', $template, 2)[1]
                    : $template;

                $path = $this->viewsDir . '/resources/views/' . $pathPart . '.blade.php';
                if (!file_exists($path)) {
                    throw new \Marko\View\Exceptions\TemplateNotFoundException(
                        "Template '{$template}' not found.",
                    );
                }

                return $path;
            });

        $factory = new BladeCompilerFactory($viewConfig, $resolver);

        return $factory->create();
    }
}
