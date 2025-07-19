<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

use Solo\QueryBuilder\Clause\OrderByClause;
use Solo\QueryBuilder\Enum\ClausePriority;

trait OrderByTrait
{
    use CapabilityBase;

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $this->filterClauses(OrderByClause::class);

        return $this->addClause(
            new OrderByClause([['column' => $column, 'direction' => $direction]], $this->getGrammar()),
            ClausePriority::ORDER_BY
        );
    }

    public function addOrderBy(string $column, string $direction = 'ASC'): static
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        return $this->addClause(
            new OrderByClause([['column' => $column, 'direction' => $direction]], $this->getGrammar()),
            ClausePriority::ORDER_BY
        );
    }
}
