<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Builder;

use Solo\QueryBuilder\Contracts\BuilderInterface;
use Solo\QueryBuilder\Contracts\ClauseInterface;
use Solo\QueryBuilder\Contracts\CompilerInterface;
use Solo\QueryBuilder\Contracts\GrammarInterface;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Cache\CacheManager;
use Solo\QueryBuilder\Capability\WhenTrait;
use Solo\QueryBuilder\Contracts\Capability\WhenCapable;
use Solo\QueryBuilder\Exception\InvalidTableException;

abstract class AbstractBuilder implements BuilderInterface, WhenCapable
{
    use WhenTrait;

    protected array $clauses = [];
    protected string $table = '';
    protected array $bindings = [];

    public function __construct(
        string $table = '',
        protected readonly CompilerInterface $compiler,
        protected ?ExecutorInterface $executor = null,
        protected ?CacheManager $cacheManager = null
    ) {
            $this->table = $table;
        }

    protected function validateTableName(): void
    {
        if (empty($this->table)) {
            throw new InvalidTableException('Table name is not specified.');
        }
    }

    public function build(): array
    {
        $this->validateTableName();
        return $this->doBuild();
    }

    abstract protected function doBuild(): array;

    protected function getGrammar(): GrammarInterface
    {
        return $this->compiler->getGrammar();
    }

    protected function addClause(ClauseInterface $clause, int $priority): static
    {
        $this->clauses[] = [
            'clause' => $clause,
            'priority' => $priority
        ];
        return $this;
    }

    protected function addBindings(array $bindings): static
    {
        $this->bindings = array_merge($this->bindings, $bindings);
        return $this;
    }

    protected function getClausesSql(?callable $filter = null): array
    {
        $filteredClauses = $filter
            ? array_filter($this->clauses, $filter)
            : $this->clauses;

        usort($filteredClauses, fn($a, $b) => $a['priority'] <=> $b['priority']);

        return array_map(fn($item) => $item['clause']->compileClause(), $filteredClauses);
    }

    public function toSql(): string
    {
        [$sql, ] = $this->build();
        return $sql;
    }

    public function getBindings(): array
    {
        $all = $this->bindings;
        foreach ($this->clauses as $item) {
            $all = array_merge($all, $item['clause']->bindings());
        }
        return $all;
    }

    protected function validateExecutor(): void
    {
        if (!$this->executor) {
            throw new \RuntimeException('No executor available to execute the query');
        }
    }
}