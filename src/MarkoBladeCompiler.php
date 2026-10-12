<?php

declare(strict_types=1);

namespace Marko\View\Blade;

use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Compilers\ComponentTagCompiler;

class MarkoBladeCompiler extends BladeCompiler
{
    protected function compileComponentTags($value)
    {
        if (!$this->compilesComponentTags) {
            return $value;
        }

        // Laravel guesses App\View\Components classes through its Application, which Marko does not have.
        $compiler = new class ($this->classComponentAliases, $this->classComponentNamespaces, $this) extends ComponentTagCompiler {
            public function guessClassName(string $component): string
            {
                return 'Marko\\View\\Blade\\NoComponentClass';
            }

            public function compileSlots(string $value)
            {
                // Laravel closes slots with ' @endslot' and no trailing space, so text after </x-slot> joins the directive name.
                return str_replace(' @endslot', ' @endslot ', parent::compileSlots($value));
            }
        };

        return $compiler->compile($value);
    }
}
