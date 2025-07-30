<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts\Capability;

interface SetCapable
{
    public function set(string|array $column, mixed $value = null): static;
}
