<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

use Solo\QueryBuilder\Enum\ClausePriority;

trait WhereTrait
{
    use ConditionTrait;

    public function where(string|\Closure $expr, mixed ...$bindings): static
    {
        return $this->addCondition('Where', ClausePriority::WHERE, 'AND', $expr, $bindings);
    }

    public function andWhere(string|\Closure $expr, mixed ...$bindings): static
    {
        return $this->where($expr, ...$bindings);
    }

    public function orWhere(string|\Closure $expr, mixed ...$bindings): static
    {
        return $this->addCondition('Where', ClausePriority::WHERE, 'OR', $expr, $bindings);
    }

    public function whereIn(string $column, array $values): static
    {
        if (empty($values)) {
            return $this;
        }

        $placeholders = rtrim(str_repeat('?, ', count($values)), ', ');
        $expr = "{$column} IN ({$placeholders})";

        return $this->where($expr, ...$values);
    }

    public function andWhereIn(string $column, array $values): static
    {
        return $this->whereIn($column, $values);
    }

    public function orWhereIn(string $column, array $values): static
    {
        if (empty($values)) {
            return $this;
        }

        $placeholders = rtrim(str_repeat('?, ', count($values)), ', ');
        $expr = "{$column} IN ({$placeholders})";

        return $this->orWhere($expr, ...$values);
    }
}
