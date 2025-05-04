<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts\Capability;

interface HavingCapable
{
    public function having(string|\Closure $expr, mixed ...$bindings): static;

    public function orHaving(string|\Closure $expr, mixed ...$bindings): static;
}