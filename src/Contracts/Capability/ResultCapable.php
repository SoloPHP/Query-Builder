<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts\Capability;

interface ResultCapable
{
    public function getAssoc(): ?array;

    public function getAllAssoc(): array;

    public function getObj(string $className = 'stdClass'): ?object;

    public function getAllObj(string $className = 'stdClass'): array;

    public function getValue(): mixed;

    public function getColumn(string $column, ?string $keyColumn = null): array;

    public function exists(): bool;

    public function count(?string $column = null, bool $distinct = false): int;
}