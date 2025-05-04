<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Builder;

use Solo\QueryBuilder\Cache\CacheManager;
use Solo\QueryBuilder\Contracts\CompilerInterface;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
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

    public function __construct(
        private readonly string $table,
        CompilerInterface $compiler,
        ?ExecutorInterface $executor = null,
        ?CacheManager $cacheManager = null
    ) {
        parent::__construct($compiler, $executor, $cacheManager);
    }

    public function build(): array
    {
        usort($this->clauses, fn($a, $b) => $a['priority'] <=> $b['priority']);

        $clausesSql = array_map(fn($item) => $item['clause']->toSql(), $this->clauses);
        $sql = $this->compiler->compileDelete($this->table, $clausesSql);

        return [$sql, $this->getBindings()];
    }
}