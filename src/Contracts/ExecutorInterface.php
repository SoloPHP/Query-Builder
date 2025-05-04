<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts;

interface ExecutorInterface
{
    public function query(string $sql, array $bindings = []): self;

    public function fetchAll(?string $type = null, ?string $className = null): array;

    public function fetch(?string $type = null, ?string $className = null): array|object|null;

    public function fetchColumn(int $idx = 0): mixed;

    public function lastInsertId(): string|int|false;

    public function rowCount(): int;

    public function beginTransaction(): bool;

    public function inTransaction(): bool;

    public function commit(): bool;

    public function rollBack(): bool;
}