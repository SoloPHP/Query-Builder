<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Builder;

use Solo\QueryBuilder\Capability\{JoinTrait, WhereTrait, SetTrait, ExecutableTrait};
use Solo\QueryBuilder\Contracts\Capability\{JoinCapable, WhereCapable, SetCapable, ExecutableCapable};

class UpdateBuilder extends AbstractBuilder implements
    WhereCapable,
    JoinCapable,
    SetCapable,
    ExecutableCapable
{
    public const TYPE = 'Update';

    use WhereTrait;
    use JoinTrait;
    use SetTrait;
    use ExecutableTrait;

    protected function doBuild(): array
    {
        $clauseObjects = $this->getClauseObjects();
        $sql = $this->compiler->compileUpdate($this->table, $clauseObjects);
        return [$sql, $this->getBindings()];
    }
}
