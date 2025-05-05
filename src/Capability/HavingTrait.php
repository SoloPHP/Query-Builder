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

    public function orHaving(string|\Closure $expr, mixed ...$bindings): static
    {
        return $this->addCondition('Having', ClausePriority::HAVING, 'OR', $expr, $bindings);
    }
}