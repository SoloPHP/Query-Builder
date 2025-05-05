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

abstract class AbstractBuilder implements BuilderInterface, WhenCapable
{
    use WhenTrait;

    protected array $clauses = [];
    protected string $table = '';

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
            throw new \InvalidArgumentException('Table name is not specified.');
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

    protected function compileClauses(): string
    {
        usort($this->clauses, fn($a, $b) => $a['priority'] <=> $b['priority']);

        $pieces = array_map(
            fn($item): string => $item['clause']->compileClause(),
            $this->clauses
        );
        return trim(implode(' ', $pieces));
    }

    protected function getClausesSql(): array
    {
        usort($this->clauses, fn($a, $b) => $a['priority'] <=> $b['priority']);
        return array_map(fn($item) => $item['clause']->compileClause(), $this->clauses);
    }

    protected function getFilteredClausesSql(callable $filter): array
    {
        $filteredClauses = array_filter($this->clauses, $filter);
        usort($filteredClauses, fn($a, $b) => $a['priority'] <=> $b['priority']);
        return array_map(fn($item) => $item['clause']->compileClause(), $filteredClauses);
    }

    public function toSql(): string
    {
        [$sql, $bindings] = $this->build();
        return $sql;
    }

    public function getBindings(): array
    {
        $all = [];
        foreach ($this->clauses as $item) {
            $all = array_merge($all, $item['clause']->bindings());
        }
        return $all;
    }
}