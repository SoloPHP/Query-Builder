<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Clause;

use Solo\QueryBuilder\Contracts\ClauseInterface;

final readonly class SetClause implements ClauseInterface
{
    public function __construct(
        private array $assignments,
        private array $bindings
    ) {
    }

    public function toSql(): string
    {
        return 'SET ' . implode(', ', $this->assignments);
    }

    public function bindings(): array
    {
        return $this->bindings;
    }
}