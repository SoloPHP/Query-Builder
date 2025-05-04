<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts;

interface ClauseInterface
{
    public function toSql(): string;

    public function bindings(): array;
}
