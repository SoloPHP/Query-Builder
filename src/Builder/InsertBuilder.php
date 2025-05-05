<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Builder;

use Solo\QueryBuilder\Cache\CacheManager;
use Solo\QueryBuilder\Contracts\CompilerInterface;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
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

    public function __construct(
        string $table,
        CompilerInterface $compiler,
        ?ExecutorInterface $executor = null,
        ?CacheManager $cacheManager = null
    ) {
        parent::__construct($compiler, $executor, $cacheManager, $table);
    }

    public function build(): array
    {
        if (empty($this->table)) {
            throw new \InvalidArgumentException('Table name is not specified.');
        }

        $sql = $this->compiler->compileInsert(
            $this->table,
            $this->columns,
            $this->rows
        );

        return [$sql, $this->getAllBindings()];
    }
}