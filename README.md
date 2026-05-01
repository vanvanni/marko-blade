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

## Differences from Latte

- **Strict Types**: Blade does not support `strict_types` because compiled templates are `include`d at runtime.
- **Auto Refresh**: When `auto_refresh` is `false`, templates are only compiled once. In production, you should set this
  to `false`.

## License

MIT
