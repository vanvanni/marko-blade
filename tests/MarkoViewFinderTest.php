<?php

declare(strict_types=1);

namespace Marko\View\Blade\Tests;

use Marko\View\Blade\MarkoViewFinder;
use Marko\View\Exceptions\InvalidTemplateException;
use Marko\View\Exceptions\TemplateNotFoundException;
use Marko\View\TemplateResolverInterface;
use PHPUnit\Framework\TestCase;

class MarkoViewFinderTest extends TestCase
{
    public function testFindConvertsDotsToSlashes(): void
    {
        $resolver = $this->createMock(TemplateResolverInterface::class);
        $finder = new MarkoViewFinder($resolver, '.blade.php');

        $resolver->expects($this->once())
            ->method('resolve')
            ->with('blog::post/show')
            ->willReturn('/path/to/blog/resources/views/post/show.blade.php');

        $path = $finder->find('blog::post.show');

        $this->assertSame('/path/to/blog/resources/views/post/show.blade.php', $path);
    }

    public function testFindPassesModulePathsAsIs(): void
    {
        $resolver = $this->createMock(TemplateResolverInterface::class);
        $finder = new MarkoViewFinder($resolver);

        $resolver->expects($this->once())
            ->method('resolve')
            ->with('admin-panel::dashboard/index')
            ->willReturn('/path/to/admin/resources/views/dashboard/index.blade.php');

        $path = $finder->find('admin-panel::dashboard/index');

        $this->assertSame('/path/to/admin/resources/views/dashboard/index.blade.php', $path);
    }

    public function testFindThrowsInvalidArgumentOnMissingTemplate(): void
    {
        $resolver = $this->createMock(TemplateResolverInterface::class);
        $finder = new MarkoViewFinder($resolver);

        $resolver->expects($this->once())
            ->method('resolve')
            ->willThrowException(TemplateNotFoundException::forTemplate('blog::missing', []));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('View [blog::missing] not found.');

        $finder->find('blog::missing');
    }

    public function testGetExtensions(): void
    {
        $resolver = $this->createMock(TemplateResolverInterface::class);
        $finder = new MarkoViewFinder($resolver, '.blade.php');

        $this->assertSame(['.blade.php'], $finder->getExtensions());
    }

    public function testParentSegmentsNeverReachTheGlobalFallback(): void
    {
        $base = sys_get_temp_dir() . '/marko-blade-finder-' . uniqid();
        mkdir($base . '/views', 0777, true);
        file_put_contents($base . '/secret.blade.php', 'secret');

        $resolver = $this->createMock(TemplateResolverInterface::class);
        $resolver->method('resolve')
            ->willThrowException(TemplateNotFoundException::forTemplate('../secret', []));

        $finder = new MarkoViewFinder($resolver, '.blade.php', $base . '/views');

        $this->expectException(\InvalidArgumentException::class);

        try {
            $finder->find('../secret');
        } finally {
            unlink($base . '/secret.blade.php');
            rmdir($base . '/views');
            rmdir($base);
        }
    }

    public function testInvalidTemplateNamesAreReportedAsInvalidArgument(): void
    {
        $resolver = $this->createMock(TemplateResolverInterface::class);
        $resolver->method('resolve')
            ->willThrowException(InvalidTemplateException::invalidName('blog::a/../b', "it contains a '..' path segment"));

        $finder = new MarkoViewFinder($resolver);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('View [blog::a/../b] is invalid');

        $finder->find('blog::a/../b');
    }

    public function testBareTemplateNamesFallBackToGlobalViews(): void
    {
        $base = sys_get_temp_dir() . '/marko-blade-finder-' . uniqid();
        mkdir($base, 0777, true);
        file_put_contents($base . '/welcome.blade.php', 'hi');

        $resolver = $this->createMock(TemplateResolverInterface::class);
        $resolver->method('resolve')
            ->willThrowException(TemplateNotFoundException::forTemplate('welcome', []));

        $finder = new MarkoViewFinder($resolver, '.blade.php', $base);

        try {
            $this->assertSame($base . '/welcome.blade.php', $finder->find('welcome'));
        } finally {
            unlink($base . '/welcome.blade.php');
            rmdir($base);
        }
    }

    public function testNamespacedTemplatesDoNotUseGlobalFallback(): void
    {
        $resolver = $this->createMock(TemplateResolverInterface::class);
        $resolver->method('resolve')
            ->willThrowException(TemplateNotFoundException::forTemplate('blog::missing', []));

        $finder = new MarkoViewFinder($resolver, '.blade.php', sys_get_temp_dir());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('View [blog::missing] not found.');

        $finder->find('blog::missing');
    }
}
