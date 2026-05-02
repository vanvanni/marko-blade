# Marko Blade

The past 10 years I have been running Laravel, brought there due to Blade one day. Now I developed a great love for
Blade templating engine and wanted to bring it to the Marko Framework.

## Installation

```bash
composer require vanvanni/marko-blade
```

This automatically installs `marko/view`.

## Configuration

Configure via the `view` config key:

```php
return [
    'cache_directory' => '/path/to/cache',
    'extension' => '.blade.php',
    'auto_refresh' => true,  // Set false in production
    'strict_types' => false, // Blade does not support strict types
];
```

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

### Blade Directives

All Blade directives work out of the box:

```blade
@extends('blog::layout')

@section('content')
    @foreach($posts as $post)
        @include('blog::post.item', ['post' => $post])
    @endforeach
@endsection
```

Includes must use the module namespace format:

```blade
@include('blog::post/list/item', ['post' => $post])
@include('blog::pagination/index', ['pagination' => $posts])
```

Relative paths (`../`) are not supported. This ensures consistent syntax throughout templates.

### Components

Anonymous Blade components are supported:

```blade
<x-blog::alert type="error" :message="$message" />
```

## Using with `marko/vite`

`marko-blade` requires `illuminate/view`, which in turn requires `illuminate/support`. Both `illuminate/support` and `marko/vite` (via `marko/env`) define a global `env()` helper.

Because both packages use `function_exists('env')` guards, no fatal error occurs. In practice, Laravel's `env()` typically loads first (it is a deeper dependency in Composer's graph) and is used. Both implementations are compatible for typical config usage — both coerce `'true'` → `true`, `'false'` → `false`, `'null'` → `null`, and `'empty'` → `''`.

`illuminate/support` lists `vlucas/phpdotenv` as a suggested dependency, but its `Env` class cannot function without it. `marko-blade` explicitly requires `vlucas/phpdotenv` so Laravel's `env()` works correctly in any Marko project, even when the framework itself is not installed.

### `@viteHeadTags` Blade Directive

When `marko/vite` is installed and enabled, `marko-blade` automatically registers a `@viteHeadTags` directive:

```blade
<!DOCTYPE html>
<html>
<head>
    @viteHeadTags
    <title>My App</title>
</head>
<body>
    ...
</body>
</html>
```

By default, it uses the entry point configured in `config/vite.php` (`vite.entry`). You can also pass a specific entry:

```blade
@viteHeadTags('app/web/resources/js/app.js')
```

In development mode (`vite.useDevServer = true`), this emits `<script type="module">` tags pointing at the Vite dev server. In production, it reads the manifest and emits hashed `<script>`, `<link rel="stylesheet">`, and `<link rel="modulepreload">` tags.

## Differences from Latte

- **Strict Types**: Blade does not support `strict_types` because compiled templates are `include`d at runtime.
- **Auto Refresh**: When `auto_refresh` is `false`, templates are only compiled once. In production, you should set this
  to `false`.

## License

MIT
