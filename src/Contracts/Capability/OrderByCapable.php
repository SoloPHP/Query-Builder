<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts\Capability;

interface OrderByCapable
{
    public function orderBy(string $column, string $direction = 'ASC'): static;

    public function addOrderBy(string $column, string $direction = 'ASC'): static;
}
