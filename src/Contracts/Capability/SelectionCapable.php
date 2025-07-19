<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts\Capability;

interface SelectionCapable
{
    public function select(string ...$cols): static;

    public function from(string $table): static;

    public function distinct(bool $value = true): static;
}
