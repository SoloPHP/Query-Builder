<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

trait WhereTrait
{
    use ConditionTrait;

    public function where(string|\Closure $expr, mixed ...$bindings): static
    {
        return $this->addCondition('Where', static::PRIORITY_WHERE, 'AND', $expr, $bindings);
    }

    public function andWhere(string|\Closure $expr, mixed ...$bindings): static
    {
        return $this->where($expr, ...$bindings);
    }

    public function orWhere(string|\Closure $expr, mixed ...$bindings): static
    {
        return $this->addCondition('Where', static::PRIORITY_WHERE, 'OR', $expr, $bindings);
    }
}