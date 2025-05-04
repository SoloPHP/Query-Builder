<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

use Solo\QueryBuilder\Clause\HavingClause;
use Solo\QueryBuilder\Condition\ConditionBuilder;

trait HavingTrait
{
    use CapabilityBase;

    protected ?ConditionBuilder $havingConditions = null;

    public function having(string|\Closure $expr, mixed ...$bindings): static
    {
        $grammar = $this->getGrammar();

        if ($this->havingConditions === null) {
            $this->havingConditions = new ConditionBuilder();
            $this->havingConditions->setGrammar($grammar);
            $this->havingConditions->where($expr, ...$bindings);
            $this->addClause(
                new HavingClause($this->havingConditions, $grammar),
                static::PRIORITY_HAVING
            );
        } else {
            $this->havingConditions->andWhere($expr, ...$bindings);
        }

        return $this;
    }

    public function orHaving(string|\Closure $expr, mixed ...$bindings): static
    {
        $grammar = $this->getGrammar();

        if ($this->havingConditions === null) {
            $this->havingConditions = new ConditionBuilder();
            $this->havingConditions->setGrammar($grammar);
            $this->havingConditions->where($expr, ...$bindings);
            $this->addClause(
                new HavingClause($this->havingConditions, $grammar),
                static::PRIORITY_HAVING
            );
        } else {
            $this->havingConditions->orWhere($expr, ...$bindings);
        }

        return $this;
    }
}