<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Builder;

use Solo\QueryBuilder\Contracts\Capability\{
    WhereCapable,
    ExecutableCapable
};
use Solo\QueryBuilder\Capability\{JoinTrait, WhereTrait, ExecutableTrait};

class DeleteBuilder extends AbstractBuilder implements
    WhereCapable,
    ExecutableCapable
{
    use WhereTrait;
    use JoinTrait;
    use ExecutableTrait;

    protected function getBuilderType(): string
    {
        return 'Delete';
    }

    protected function doBuild(): array
    {
        $clausesSql = $this->getClausesSql();
        $sql = $this->compiler->compileDelete($this->table, $clausesSql);
        return [$sql, $this->getBindings()];
    }
}