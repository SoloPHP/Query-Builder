<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Builder;

use Solo\QueryBuilder\Capability\{ValuesTrait, InsertGetIdTrait, ExecutableTrait};
use Solo\QueryBuilder\Contracts\Capability\{ValuesCapable, InsertGetIdCapable, ExecutableCapable};

class InsertBuilder extends AbstractBuilder implements
    ValuesCapable,
    InsertGetIdCapable,
    ExecutableCapable
{
    use ValuesTrait;
    use InsertGetIdTrait;
    use ExecutableTrait;

    public const TYPE = 'Insert';

    protected function doBuild(): array
    {
        $clauseObjects = $this->getClauseObjects();
        $sql = $this->compiler->compileInsert($this->table, $this->columns, $this->rows);
        return [$sql, $this->getBindings()];
    }
}
