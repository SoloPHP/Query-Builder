<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts\Capability;

interface GroupByCapable
{
    public function groupBy(string ...$cols): static;
}