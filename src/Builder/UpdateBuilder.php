<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Builder;

use Solo\QueryBuilder\Capability\{WhereTrait, SetTrait, ExecutableTrait};
use Solo\QueryBuilder\Contracts\Capability\{WhereCapable, SetCapable, ExecutableCapable};

class UpdateBuilder extends AbstractBuilder implements
    WhereCapable,
    SetCapable,
    ExecutableCapable
{
    use WhereTrait;
    use SetTrait;
    use ExecutableTrait;

    public const TYPE = 'Update';

    protected function doBuild(): array
    {
        $clauseObjects = $this->getClauseObjects();
        $sql = $this->compiler->compileUpdate($this->table, $clauseObjects);
        return [$sql, $this->getBindings()];
    }
}
