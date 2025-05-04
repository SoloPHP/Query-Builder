<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts\Capability;

interface WhereCapable
{
    public function where(string|\Closure $expr, mixed ...$bindings): static;

    public function orWhere(string|\Closure $expr, mixed ...$bindings): static;

    public function andWhere(string|\Closure $expr, mixed ...$bindings): static;
}