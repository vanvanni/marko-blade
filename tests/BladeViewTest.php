<?php

declare(strict_types=1);

namespace Marko\View\Blade\Tests;

use Illuminate\View\Factory;
use Marko\Routing\Http\Response;
use Marko\View\Blade\BladeView;
use PHPUnit\Framework\TestCase;

class BladeViewTest extends TestCase
{
    public function testImplementsViewInterface(): void
    {
        $factory = $this->createMock(Factory::class);
        $view = new BladeView($factory);

        $this->assertInstanceOf(\Marko\View\ViewInterface::class, $view);
    }

    public function testRenderReturnsResponse(): void
    {
        $factory = $this->createMock(Factory::class);
        $illuminateView = $this->createMock(\Illuminate\Contracts\View\View::class);

        $factory->expects($this->once())
            ->method('make')
            ->with('blog::post/show', ['post' => ['id' => 1]])
            ->willReturn($illuminateView);

        $illuminateView->expects($this->once())
            ->method('render')
            ->willReturn('<h1>Hello</h1>');

        $view = new BladeView($factory);
        $response = $view->render('blog::post/show', ['post' => ['id' => 1]]);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testRenderToStringReturnsHtml(): void
    {
        $factory = $this->createMock(Factory::class);
        $illuminateView = $this->createMock(\Illuminate\Contracts\View\View::class);

        $factory->expects($this->once())
            ->method('make')
            ->with('blog::post/show', ['post' => ['id' => 1]])
            ->willReturn($illuminateView);

        $illuminateView->expects($this->once())
            ->method('render')
            ->willReturn('<h1>Hello</h1>');

        $view = new BladeView($factory);
        $html = $view->renderToString('blog::post/show', ['post' => ['id' => 1]]);

        $this->assertSame('<h1>Hello</h1>', $html);
    }
}
