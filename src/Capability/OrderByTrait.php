<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

use Solo\QueryBuilder\Clause\OrderByClause;

trait OrderByTrait
{
    use CapabilityBase;

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $this->clauses = array_filter($this->clauses, function ($item) {
            return !($item['clause'] instanceof OrderByClause);
        });

        return $this->addClause(
            new OrderByClause([['column' => $column, 'direction' => $direction]], $this->getGrammar()),
            static::PRIORITY_ORDER_BY
        );
    }

    public function addOrderBy(string $column, string $direction = 'ASC'): static
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        return $this->addClause(
            new OrderByClause([['column' => $column, 'direction' => $direction]], $this->getGrammar()),
            static::PRIORITY_ORDER_BY
        );
    }
}