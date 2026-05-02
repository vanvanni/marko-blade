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