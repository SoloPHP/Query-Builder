<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts;

interface ClauseInterface
{
    public function compileClause(): string;

    public function bindings(): array;
}
