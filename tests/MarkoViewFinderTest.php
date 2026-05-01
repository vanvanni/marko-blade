<?php

declare(strict_types=1);

namespace Marko\View\Blade\Tests;

use Marko\View\Blade\MarkoViewFinder;
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
}
