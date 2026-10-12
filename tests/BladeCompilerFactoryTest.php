<?php

declare(strict_types=1);

namespace Marko\View\Blade\Tests;

class BladeCompilerFactoryTest extends BladeTestCase
{
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
}
