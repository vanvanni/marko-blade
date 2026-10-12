<?php

declare(strict_types=1);

namespace Marko\View\Blade\Tests;

use Marko\Config\ConfigRepository;
use Marko\Config\ConfigRepositoryInterface;
use Marko\Core\Container\Container as MarkoContainer;
use Marko\Routing\UrlGeneratorInterface;

class BladeDirectivesTest extends BladeTestCase
{
    private function render(string $template, array $data = [], ?MarkoContainer $marko = null): string
    {
        $this->writeView('post/page', $template);

        return preg_replace('/\s+/', ' ', trim($this->createFactory(markoContainer: $marko)->make('blog::post/page', $data)->render()));
    }

    public function testConditionalsAndSwitch(): void
    {
        $html = $this->render(<<<'B'
@if(false) a @elseif(true) b @else c @endif
@unless(true) x @endunless
@isset($nope) y @endisset
@empty($empty) empty @endempty
@switch(2) @case(1) one @break @case(2) two @break @default d @endswitch
B, ['empty' => []]);

        $this->assertSame('b empty two', $html);
    }

    public function testLoopsWithLoopVariableAndControlFlow(): void
    {
        $html = $this->render(<<<'B'
@foreach([1, 2] as $n){{ $loop->iteration }}:{{ $n }} @endforeach|
@forelse([] as $n) x @empty none @endforelse|
@for($i = 0; $i < 2; $i++)[{{ $i }}]@endfor|
@php $w = 0; @endphp
@while($w < 2) {{ $w++ }} @endwhile|
@foreach([1, 2, 3] as $n) @continue($n === 2) {{ $n }} @break($n === 3) @endforeach
B);

        $this->assertSame('1:1 2:2 | none | [0][1]| 0 1 | 1 3', $html);
    }

    public function testLayoutsSectionsAndStacks(): void
    {
        $this->writeView('layouts/app', <<<'B'
<title>@yield('title', 'Default')</title>@stack('scripts')<main>@yield('content')</main><aside>@yield('side', 'none')</aside>@hasSection('side')<em>has</em>@endif
B);
        $this->writeView('post/child', <<<'B'
@extends('blog::layouts.app')
@section('title', 'Posts')
@push('scripts')<script>one</script>@endpush
@prepend('scripts')<script>zero</script>@endprepend
@section('content')Hello @endsection
@section('side', 'S')
B);

        $html = $this->createFactory()->make('blog::post/child')->render();

        $this->assertSame(
            '<title>Posts</title><script>zero</script><script>one</script><main>Hello</main><aside>S</aside><em>has</em>',
            preg_replace('/\s+/', '', $html),
        );
    }

    public function testParentAndShowInSections(): void
    {
        $this->writeView('layouts/base', "@section('nav')base @show");
        $this->writeView('post/child', "@extends('blog::layouts.base')\n@section('nav')child @parent @endsection");

        $this->assertSame('child base', preg_replace('/\s+/', ' ', trim($this->createFactory()->make('blog::post/child')->render())));
    }

    public function testIncludesAndEach(): void
    {
        $this->writeView('post/partial', '<em>{{ $label ?? "none" }}</em>');

        $html = $this->render(
            "@include('blog::post.partial', ['label' => 'inc'])"
            . "@includeIf('blog::post.missing')"
            . "@includeWhen(true, 'blog::post.partial', ['label' => 'when'])"
            . "@includeUnless(true, 'blog::post.partial')"
            . "@includeFirst(['blog::post.missing', 'blog::post.partial'], ['label' => 'first'])"
            . "@each('blog::post.partial', ['a', 'b'], 'label')",
        );

        $this->assertSame('<em>inc</em><em>when</em><em>first</em><em>a</em><em>b</em>', $html);
    }

    public function testComponentsWithPropsAttributesAndSlots(): void
    {
        $this->writeView('components/alert', <<<'B'
@props(['type' => 'info'])
<div {{ $attributes->merge(['class' => 'alert alert-'.$type]) }}>{{ $slot }}</div>
B);
        $this->writeView('components/panel', '<section><h2>{{ $title }}</h2>{{ $slot }}</section>');

        $html = $this->render(
            '<x-blog::alert type="error" class="mb-2">Boom</x-blog::alert>'
            . '<x-blog::panel><x-slot:title>Head</x-slot:title>Body</x-blog::panel>',
        );

        $this->assertSame(
            '<div class="alert alert-error mb-2">Boom</div><section><h2>Head</h2>Body</section>',
            $html,
        );
    }

    public function testEscapingVerbatimAndHtmlAttributeDirectives(): void
    {
        $html = $this->render(
            '{{ "<b>" }} {!! "<i>" !!} @{{ literal }} {{-- hidden --}}'
            . '@verbatim {{ raw }} @endverbatim '
            . '<input @checked(true) @disabled(false) @readonly(true) @required(true)>'
            . '<option @selected(true)></option>'
            . '<div @class([\'a\', \'b\' => true, \'c\' => false]) @style([\'color: red\', \'x\' => false])></div>'
            . '@json([\'a\' => 1])',
        );

        $this->assertSame(
            '&lt;b&gt; <i> {{ literal }} {{ raw }} <input checked readonly required><option selected></option><div class="a b" style="color: red;"></div>{"a":1}',
            $html,
        );
    }

    public function testOnceRendersOncePerRender(): void
    {
        $html = $this->render('@foreach([1, 2] as $n) @once<u>o</u>@endonce @endforeach');

        $this->assertSame('<u>o</u>', $html);
    }

    public function testEnvironmentDirectivesFollowAppEnv(): void
    {
        $previous = $_ENV['APP_ENV'] ?? null;
        $template = '@production prod @endproduction @env(["local", "testing"]) dev @endenv';

        try {
            $_ENV['APP_ENV'] = 'production';
            $this->assertSame('prod', $this->render($template));

            $_ENV['APP_ENV'] = 'local';
            $this->assertSame('dev', $this->render($template));
        } finally {
            if ($previous === null) {
                unset($_ENV['APP_ENV']);
            } else {
                $_ENV['APP_ENV'] = $previous;
            }
        }
    }

    public function testAwareFragmentsJsAndPushOnce(): void
    {
        $this->writeView('components/group', '@props([\'color\']) <div>{{ $slot }}</div>');
        $this->writeView('components/item', '@aware([\'color\' => \'black\']) <b class="{{ $color }}">{{ $slot }}</b>');

        $html = $this->render(
            '<x-blog::group color="blue"><x-blog::item>A</x-blog::item></x-blog::group>'
            . '@fragment(\'frag\')<i>F</i>@endfragment '
            . '@js([\'a\' => \'x\']) '
            . '@pushOnce(\'scripts\', \'key\')<s>once</s>@endPushOnce @pushOnce(\'scripts\', \'key\')<s>twice</s>@endPushOnce @stack(\'scripts\')',
        );

        $this->assertSame('<div><b class="blue">A</b></div><i>F</i> JSON.parse(\'{\u0022a\u0022:\u0022x\u0022}\') <s>once</s>', $html);
    }

    public function testConfigHelperReadsMarkoConfiguration(): void
    {
        $marko = new MarkoContainer();
        $marko->instance(ConfigRepositoryInterface::class, new ConfigRepository(['app' => ['name' => 'Marko']]));

        $this->assertSame('Marko fallback', $this->render("{{ config('app.name') }} {{ config('app.missing', 'fallback') }}", [], $marko));
    }

    public function testRouteHelperUsesMarkoUrlGenerator(): void
    {
        $generator = new class implements UrlGeneratorInterface {
            public function route(string $name, array $parameters = [], bool $absolute = false): string
            {
                return '/' . $name . '/' . $parameters['id'];
            }
        };

        $marko = new MarkoContainer();
        $marko->instance(UrlGeneratorInterface::class, $generator);

        $this->assertSame('/blog.show/7', $this->render("{{ route('blog.show', ['id' => 7]) }}", [], $marko));
    }
}
