<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

use Solo\QueryBuilder\Condition\ConditionBuilder;

trait ConditionTrait
{
    use CapabilityBase;

    protected ?ConditionBuilder $conditions = null;

    protected function addCondition(string $type, int $priority, string $logicalOperator, string|\Closure $expr, array $bindings): static
    {
        $grammar = $this->getGrammar();

        if ($this->conditions === null) {
            $this->conditions = new ConditionBuilder();
            $this->conditions->setGrammar($grammar);
            $this->conditions->where($expr, ...$bindings);

            $clauseClass = "Solo\\QueryBuilder\\Clause\\{$type}Clause";
            $this->addClause(
                new $clauseClass($this->conditions, $grammar),
                $priority
            );
        } else {
            if ($logicalOperator === 'AND') {
                $this->conditions->andWhere($expr, ...$bindings);
            } else {
                $this->conditions->orWhere($expr, ...$bindings);
            }
        }

        return $this;
    }
}