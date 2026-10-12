<?php

declare(strict_types=1);

if (!function_exists('url')) {
    /**
     * Generate an absolute URL.
     *
     * @param string $path The path to append to the base URL.
     * @param array $parameters Query string parameters to append.
     *
     * @return string The absolute URL.
     */
    function url(string $path = '', array $parameters = []): string
    {
        $baseUrl = rtrim($_ENV['APP_URL'] ?? '', '/');

        if ($baseUrl === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $baseUrl = $scheme . '://' . $host;
        }

        if ($path !== '') {
            $baseUrl .= '/' . ltrim($path, '/');
        }

        if (!empty($parameters)) {
            $baseUrl .= '?' . http_build_query($parameters);
        }

        return $baseUrl;
    }
}

if (!function_exists('current_url')) {
    /**
     * Get the current URL
     *
     * @return string The current URL where we are at
     */
    function current_url(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 0) == 443 ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return $scheme . '://' . $host . $uri;
    }
}

if (!function_exists('route')) {
    /**
     * Build the URL for a named route.
     *
     * @param string $name The route name.
     * @param array<string, mixed> $parameters Route path values; unused keys become the query string.
     * @param bool $absolute Prefix the URL with the configured base URL (routing.url).
     *
     * @return string The route URL.
     */
    function route(string $name, array $parameters = [], bool $absolute = false): string
    {
        return \Illuminate\Container\Container::getInstance()
            ->make(\Marko\Routing\UrlGeneratorInterface::class)
            ->route($name, $parameters, $absolute);
    }
}

if (!function_exists('config')) {
    /**
     * Read a configuration value.
     *
     * @param string|null $key Dot-separated key such as 'app.name'. Null returns all configuration.
     * @param mixed $default Returned when the key is not set.
     *
     * @return mixed The configuration value.
     */
    function config(?string $key = null, mixed $default = null): mixed
    {
        $config = \Illuminate\Container\Container::getInstance()
            ->make(\Marko\Config\ConfigRepositoryInterface::class);

        if ($key === null) {
            return $config->all();
        }

        return $config->has($key) ? $config->get($key) : $default;
    }
}