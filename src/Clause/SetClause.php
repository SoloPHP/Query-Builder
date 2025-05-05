<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Clause;

use Solo\QueryBuilder\Contracts\ClauseInterface;
use Solo\QueryBuilder\Enum\ClausePriority;

final readonly class SetClause implements ClauseInterface
{
    public const TYPE = ClausePriority::SET;

    public function __construct(
        private array $assignments,
        private array $bindings
    ) {
    }

    public function compileClause(): string
    {
        return 'SET ' . implode(', ', $this->assignments);
    }

    public function bindings(): array
    {
        return $this->bindings;
    }
}