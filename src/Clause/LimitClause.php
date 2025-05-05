<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Clause;

use Solo\QueryBuilder\Contracts\ClauseInterface;

final readonly class LimitClause implements ClauseInterface
{
    public function __construct(private int $limit, private ?int $offset = null)
    {
    }

    public function compileClause(): string
    {
        return $this->offset === null
            ? "LIMIT {$this->limit}"
            : "LIMIT {$this->limit} OFFSET {$this->offset}";
    }

    public function bindings(): array
    {
        return [];
    }
}