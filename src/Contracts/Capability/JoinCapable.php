<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts\Capability;

interface JoinCapable
{
    public function join(string $table, string $condition, mixed ...$bindings): static;

    public function leftJoin(string $table, string $condition, mixed ...$bindings): static;

    public function rightJoin(string $table, string $condition, mixed ...$bindings): static;

    public function fullJoin(string $table, string $condition, mixed ...$bindings): static;

    public function joinSub(\Closure $callback, string $alias, string $condition, mixed ...$bindings): static;
}
