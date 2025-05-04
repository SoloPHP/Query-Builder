<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts\Capability;

interface ValuesCapable
{
    public function values(array $data): static;
}