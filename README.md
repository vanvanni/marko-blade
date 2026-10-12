# Marko Blade

The past 10 years I have been running Laravel, brought there due to Blade one day. Now I developed a great love for
Blade templating engine and wanted to bring it to the Marko Framework.

## Requirements

- PHP 8.5+
- Marko Framework 0.11 (`marko/view` ^0.11)

## Installation

```bash
composer require vanvanni/marko-blade
```

This automatically installs `marko/view`. For Vite asset tags, also install `marko/vite`:

```bash
composer require marko/vite
```

## Configuration

Configure via the `view` config key:

```php
return [
    'cache_directory' => 'storage/cache/views', // Relative to the project root, or an absolute POSIX path
    'extension' => '.blade.php',
    'auto_refresh' => true,  // Set false in production
];
```

Compiled templates are PHP files that get executed, so the cache directory must not be world-writable. On Linux and
macOS the directory is created with mode `0700` and checked before use.

## Usage

Templates are rendered using the module namespace syntax:

```php
use Marko\View\ViewInterface;

$view->render('blog::post/index', ['posts' => $posts]);
```

The format is `module::path/to/template` where:

- `module` is the module name (e.g., `blog`, `admin`)
- `path/to/template` is the path within `resources/views/`

Use `renderToString()` when you need the raw HTML:

```php
$html = $view->renderToString('blog::email/welcome', $data);
```

Templates are referenced with `/` or Blade's dot notation (`blog::post.show` is the same as `blog::post/show`). Dots
cannot appear in file names.

## Blade Directives

### Supported

Every directive below is covered by the test suite.

| Area | Directives |
|------|------------|
| Output | `{{ }}`, `{!! !!}`, `{{-- --}}`, `@{{ }}`, `@verbatim`, `@php`, `@json`, `@js` |
| Conditionals | `@if`, `@elseif`, `@else`, `@unless`, `@isset`, `@empty`, `@switch`, `@case`, `@default` |
| Loops | `@for`, `@foreach`, `@forelse`, `@while`, `@continue`, `@break`, `$loop` |
| Layouts | `@extends`, `@section`, `@yield`, `@parent`, `@show`, `@hasSection`, `@fragment` |
| Stacks | `@push`, `@prepend`, `@stack`, `@once`, `@pushOnce` |
| Includes | `@include`, `@includeIf`, `@includeWhen`, `@includeUnless`, `@includeFirst`, `@each` |
| Components | `<x-module::name>`, `@props`, `@aware`, `$attributes`, `$slot`, `<x-slot:name>` |
| HTML attributes | `@class`, `@style`, `@checked`, `@selected`, `@disabled`, `@readonly`, `@required` |
| Environment | `@env`, `@production` (read `APP_ENV`, defaults to `local`) |
| Assets | `@vite`, `@viteHeadTags`, `@viteReactRefresh` (see below) |

Relative paths (`../`) are not supported in includes. Use module namespace names everywhere:

```blade
@include('blog::post/list/item', ['post' => $post])
```

### Not available

These Laravel directives depend on Laravel services this driver does not wire into Marko, so they fail at render time:

- Authorization and identity: `@auth`, `@guest`, `@can`, `@cannot`, `@canany`
- Forms and sessions: `@csrf`, `@method`, `@session`, `@error` (needs a Laravel `$errors` bag)
- Services: `@lang`, `@inject`
- Debugging: `@dd`, `@dump` (no `dump()` helper is installed)

Pass the values you need from your controller instead.

### Components

Anonymous components work across modules:

```blade
<x-blog::alert type="error" class="mb-2">Something broke</x-blog::alert>

<x-blog::panel>
    <x-slot:title>Latest posts</x-slot:title>
    ...
</x-blog::panel>
```

Put component templates in `resources/views/components/` inside the module, and merge the attribute bag in the
component template:

```blade
{{-- blog/resources/views/components/alert.blade.php --}}
@props(['type' => 'info'])
<div {{ $attributes->merge(['class' => 'alert alert-'.$type]) }}>{{ $slot }}</div>
```

## Helpers

These global helpers are available in every template:

- `url($path = '', $parameters = [])` builds an absolute URL from `APP_URL`
- `current_url()` returns the URL of the current request
- `route($name, $parameters = [], $absolute = false)` builds a named route URL through Marko's `UrlGeneratorInterface`
- `config($key = null, $default = null)` reads Marko configuration, e.g. `config('app.name')`
- `e()`, `env()`, `data_get()`, `collect()`, `optional()`, `tap()`, `value()` and the other `illuminate/support` helpers

```blade
<a href="{{ route('blog.post.show', ['slug' => $post->slug]) }}">{{ $post->title }}</a>
```

## Vite Assets

When `marko/vite` is installed, the `@vite` directive emits the script and stylesheet tags for an entry. Entries are the
input paths from your Vite config, the same keys that appear in the build manifest (for example
`app/web/resources/js/app.js`):

```blade
<head>
    @vite('resources/js/app.js')
</head>
```

Pass several entries as an array:

```blade
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

`@viteHeadTags` is the same directive under Marko's name. Without arguments it uses the entry configured as
`vite.entry` (`VITE_ENTRY`):

```blade
<head>
    @viteHeadTags
</head>
```

Marko's `marko/vite` settings control the mode:

- **Development** (`VITE_USE_DEV_SERVER=true`, the default when `APP_ENV=local`): emits `<script type="module">` tags
  pointing at the Vite dev server (`VITE_DEV_SERVER_URL`), including the React Refresh preamble for `.jsx`/`.tsx` entries.
- **Production**: reads `public/build/.vite/manifest.json` and emits hashed `<script>`, `<link rel="stylesheet">` and
  `<link rel="modulepreload">` tags. Run your Vite build before deploying.

`@viteReactRefresh` is accepted for Laravel compatibility and renders nothing, because Marko already adds the preamble
for React entries.

## Differences from Laravel

- **Strict types**: compiled templates cannot declare `strict_types`, because they are included at runtime.
- **Auto refresh**: when `auto_refresh` is `false`, templates are compiled once and reused. Set it to `false` in production.
- **Services**: see "Not available" above.

## Known Limitations

- **Windows and `marko/view` 0.11**: `marko/view`'s `ModuleTemplateResolver` (including the `dev-main` branch) compares
  `realpath()` results with a `/` separator. Windows returns `\` separators, so every template is rejected as
  "resolves outside its module's views directory". This is an upstream bug in `marko/view`, not in this package.
  Until it is fixed upstream, render through Marko's resolver on Linux or macOS. On Windows, the cache directory skips
  the POSIX permission check, because Windows reports every directory as world-writable.

## License

MIT
