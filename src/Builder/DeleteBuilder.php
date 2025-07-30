<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Builder;

use Solo\QueryBuilder\Capability\{JoinTrait, WhereTrait, ExecutableTrait};
use Solo\QueryBuilder\Contracts\Capability\{JoinCapable, WhereCapable, ExecutableCapable};

class DeleteBuilder extends AbstractBuilder implements
    WhereCapable,
    JoinCapable,
    ExecutableCapable
{
    use WhereTrait;
    use JoinTrait;
    use ExecutableTrait;

    public const TYPE = 'Delete';

    protected function doBuild(): array
    {
        $clauseObjects = $this->getClauseObjects();
        $sql = $this->compiler->compileDelete($this->table, $clauseObjects);
        return [$sql, $this->getBindings()];
    }
}
