<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

trait HavingTrait
{
    use ConditionTrait;

    public function having(string|\Closure $expr, mixed ...$bindings): static
    {
        return $this->addCondition('Having', static::PRIORITY_HAVING, 'AND', $expr, $bindings);
    }

    public function orHaving(string|\Closure $expr, mixed ...$bindings): static
    {
        return $this->addCondition('Having', static::PRIORITY_HAVING, 'OR', $expr, $bindings);
    }
}