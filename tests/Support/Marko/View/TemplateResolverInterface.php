<?php

declare(strict_types=1);

namespace Marko\View;

use Marko\View\Exceptions\TemplateNotFoundException;

interface TemplateResolverInterface
{
    public function resolve(string $template): string;
    public function getSearchedPaths(string $template): array;
}
