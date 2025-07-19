<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Builder;

use Solo\QueryBuilder\Capability\{ValuesTrait, InsertGetIdTrait};
use Solo\QueryBuilder\Contracts\Capability\{ValuesCapable, InsertGetIdCapable};

class InsertBuilder extends AbstractBuilder implements
    ValuesCapable,
    InsertGetIdCapable
{
    public const TYPE = 'Insert';

    use ValuesTrait;
    use InsertGetIdTrait;

    protected function doBuild(): array
    {
        $sql = $this->compiler->compileInsert($this->table, $this->columns, $this->rows);
        return [$sql, $this->getBindings()];
    }
}
