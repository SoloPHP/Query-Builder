<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts\Capability;

interface HavingCapable extends ConditionCapable
{
    public function orHaving(string|\Closure $expr, mixed ...$bindings): static;
}