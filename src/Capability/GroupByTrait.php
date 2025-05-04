<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

use Solo\QueryBuilder\Clause\GroupByClause;

trait GroupByTrait
{
    use CapabilityBase;

    public function groupBy(string ...$cols): static
    {
        return $this->addClause(
            new GroupByClause($cols, $this->getGrammar()),
            static::PRIORITY_GROUP_BY
        );
    }
}