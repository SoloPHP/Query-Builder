<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

use Solo\QueryBuilder\Enum\ClausePriority;

trait HavingTrait
{
    use ConditionTrait;

    public function having(string|\Closure $expr, mixed ...$bindings): static
    {
        return $this->addCondition('Having', ClausePriority::HAVING, 'AND', $expr, $bindings);
    }

    public function andHaving(string|\Closure $expr, mixed ...$bindings): static
    {
        return $this->having($expr, ...$bindings);
    }

    public function orHaving(string|\Closure $expr, mixed ...$bindings): static
    {
        return $this->addCondition('Having', ClausePriority::HAVING, 'OR', $expr, $bindings);
    }

    public function havingIn(string $column, array $values): static
    {
        if (empty($values)) {
            return $this;
        }

        $placeholders = rtrim(str_repeat('?, ', count($values)), ', ');
        $expr = "{$column} IN ({$placeholders})";

        return $this->having($expr, ...$values);
    }

    public function andHavingIn(string $column, array $values): static
    {
        return $this->havingIn($column, $values);
    }

    public function orHavingIn(string $column, array $values): static
    {
        if (empty($values)) {
            return $this;
        }

        $placeholders = rtrim(str_repeat('?, ', count($values)), ', ');
        $expr = "{$column} IN ({$placeholders})";

        return $this->orHaving($expr, ...$values);
    }
}
