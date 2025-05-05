<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts\Capability;

interface WhereCapable extends ConditionCapable
{
    public function andWhere(string|\Closure $expr, mixed ...$bindings): static;
}