<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts\Capability;

interface WhereCapable
{
    public function where(string|\Closure $expr, mixed ...$bindings): static;
    public function andWhere(string|\Closure $expr, mixed ...$bindings): static;
    public function orWhere(string|\Closure $expr, mixed ...$bindings): static;
    public function whereIn(string $column, array $values): static;
    public function andWhereIn(string $column, array $values): static;
    public function orWhereIn(string $column, array $values): static;
}
