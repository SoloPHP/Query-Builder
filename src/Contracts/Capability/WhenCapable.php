<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts\Capability;

interface WhenCapable
{
    public function when(bool $condition, callable $callback, ?callable $default = null): static;
}
