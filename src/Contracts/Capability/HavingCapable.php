<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts\Capability;

interface HavingCapable
{
    public function having(string|\Closure $expr, mixed ...$bindings): static;
    public function andHaving(string|\Closure $expr, mixed ...$bindings): static;
    public function orHaving(string|\Closure $expr, mixed ...$bindings): static;
    public function havingIn(string $column, array $values): static;
    public function andHavingIn(string $column, array $values): static;
    public function orHavingIn(string $column, array $values): static;
}
