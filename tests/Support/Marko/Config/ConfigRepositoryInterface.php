<?php

declare(strict_types=1);

namespace Marko\Config;

interface ConfigRepositoryInterface
{
    public function getString(string $key): string;
    public function getBool(string $key): bool;
    public function getInt(string $key): int;
    public function getArray(string $key): array;
    public function get(string $key, mixed $default = null): mixed;
}
