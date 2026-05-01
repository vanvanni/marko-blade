<?php

declare(strict_types=1);

namespace Marko\View\Blade;

use Illuminate\View\Factory;
use Marko\Routing\Http\Response;
use Marko\View\ViewInterface;

readonly class BladeView implements ViewInterface
{
    public function __construct(
        private Factory $factory,
    )
    {
    }

    public function render(
        string $template,
        array  $data = [],
    ): Response
    {
        $html = $this->renderToString($template, $data);

        return Response::html($html);
    }

    public function renderToString(
        string $template,
        array  $data = [],
    ): string
    {
        return $this->factory->make($template, $data)->render();
    }
}
