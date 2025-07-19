<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts\Capability;

interface LimitCapable
{
    public function limit(int $limit, ?int $offset = null): static;

    public function paginate(int $limit, int $page = 1): static;
}
