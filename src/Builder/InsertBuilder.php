<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Builder;

use Solo\QueryBuilder\Contracts\Capability\{
    ValuesCapable,
    InsertGetIdCapable
};
use Solo\QueryBuilder\Capability\{
    ValuesTrait,
    InsertGetIdTrait
};

class InsertBuilder extends AbstractBuilder implements
    ValuesCapable,
    InsertGetIdCapable
{
    use ValuesTrait;
    use InsertGetIdTrait;

    protected function doBuild(): array
    {
        $sql = $this->compiler->compileInsert(
            $this->table,
            $this->columns,
            $this->rows
        );

        return [$sql, $this->getAllBindings()];
    }
}