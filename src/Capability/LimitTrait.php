<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

use Solo\QueryBuilder\Clause\LimitClause;
use Solo\QueryBuilder\Enum\ClausePriority;

trait LimitTrait
{
    use CapabilityBase;

    public function limit(int $limit, ?int $offset = null): static
    {
        return $this->addClause(
            new LimitClause($limit, $offset),
            ClausePriority::LIMIT
        );
    }

    public function paginate(int $limit, int $page = 1): static
    {
        $offset = ($page - 1) * $limit;
        return $this->limit($limit, $offset);
    }
}
