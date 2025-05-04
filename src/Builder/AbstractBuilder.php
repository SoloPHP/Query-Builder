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

    public function __construct(
        protected readonly CompilerInterface  $compiler,
        protected ?ExecutorInterface $executor = null,
        protected ?CacheManager      $cacheManager = null
    ) {
    }

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

    public function toSql(): string
    {
        usort($this->clauses, fn($a, $b) => $a['priority'] <=> $b['priority']);

        $pieces = array_map(
            fn($item): string => $item['clause']->toSql(),
            $this->clauses
        );
        return trim(implode(' ', $pieces));
    }

    public function getBindings(): array
    {
        $all = [];
        foreach ($this->clauses as $item) {
            $all = array_merge($all, $item['clause']->bindings());
        }
        return $all;
    }

    abstract public function build(): array;
}