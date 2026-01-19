<?php
declare(strict_types=1);

namespace App\Core;

class Container
{
    private array $bindings = [];

    public function set(string $key, object|array $instance): void
    {
        $this->bindings[$key] = $instance;
    }

    public function get(string $key): object|array|null
    {
        return $this->bindings[$key] ?? null;
    }
}

