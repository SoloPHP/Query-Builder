<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

use Solo\QueryBuilder\Clause\WhereClause;
use Solo\QueryBuilder\Condition\ConditionBuilder;

trait WhereTrait
{
    use CapabilityBase;

    protected ?ConditionBuilder $whereConditions = null;

    public function where(string|\Closure $expr, mixed ...$bindings): static
    {
        return $this->addWhereCondition($expr, $bindings);
    }

    public function andWhere(string|\Closure $expr, mixed ...$bindings): static
    {
        return $this->where($expr, ...$bindings);
    }

    public function orWhere(string|\Closure $expr, mixed ...$bindings): static
    {
        return $this->addOrWhereCondition($expr, $bindings);
    }

    protected function addWhereCondition(string|\Closure $expr, array $bindings): static
    {
        $grammar = $this->getGrammar();

        if ($this->whereConditions === null) {
            $this->whereConditions = new ConditionBuilder();
            $this->whereConditions->setGrammar($grammar);
            $this->whereConditions->where($expr, ...$bindings);
            $this->addClause(new WhereClause($this->whereConditions, $grammar), static::PRIORITY_WHERE);
        } else {
            $this->whereConditions->andWhere($expr, ...$bindings);
        }

        return $this;
    }

    protected function addOrWhereCondition(string|\Closure $expr, array $bindings): static
    {
        $grammar = $this->getGrammar();

        if ($this->whereConditions === null) {
            $this->whereConditions = new ConditionBuilder();
            $this->whereConditions->setGrammar($grammar);
            $this->whereConditions->where($expr, ...$bindings);
            $this->addClause(new WhereClause($this->whereConditions, $grammar), static::PRIORITY_WHERE);
        } else {
            $this->whereConditions->orWhere($expr, ...$bindings);
        }

        return $this;
    }
}