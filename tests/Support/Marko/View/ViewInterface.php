<?php

declare(strict_types=1);

namespace Marko\View;

use Marko\Routing\Http\Response;

interface ViewInterface
{
    public function render(string $template, array $data = []): Response;
    public function renderToString(string $template, array $data = []): string;
}
