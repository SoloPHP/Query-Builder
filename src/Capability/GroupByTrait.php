<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

use Solo\QueryBuilder\Clause\GroupByClause;
use Solo\QueryBuilder\Enum\ClausePriority;

trait GroupByTrait
{
    use CapabilityBase;

    public function groupBy(string ...$cols): static
    {
        return $this->addClause(
            new GroupByClause($cols, $this->getGrammar()),
            ClausePriority::GROUP_BY
        );
    }
}
