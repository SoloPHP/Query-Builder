<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Executors\PdoExecutor;

use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Exception\QueryBuilderException;

use PDO;
use PDOStatement;
use Throwable;

final class PdoExecutor implements ExecutorInterface
{
    private PDO $pdo;
    private int $defaultFetch;
    private ?PDOStatement $stmt = null;

    public function __construct(Connection $connection)
    {
        $this->pdo = $connection->pdo();
        $this->defaultFetch = $connection->fetchMode();
    }

    public function query(string $sql, array $bindings = []): self
    {
        return $this->wrap(function () use ($sql, $bindings) {
            $this->stmt = $this->pdo->prepare($sql);
            $this->stmt->execute($bindings);
            return $this;
        }, 'Query execution failed');
    }

    public function fetchAll(?string $type = null, ?string $className = null): array
    {
        if (!$this->stmt) {
            return [];
        }

        return $this->wrap(function () use ($type, $className) {
            $fetchMode = $this->getFetchMode($type);

            if ($fetchMode === PDO::FETCH_CLASS && $className !== null) {
                return $this->stmt->fetchAll($fetchMode, $className);
            }

            return $this->stmt->fetchAll($fetchMode);
        }, 'Error fetching results');
    }

    public function fetch(?string $type = null, ?string $className = null): array|object|null
    {
        if (!$this->stmt) {
            return null;
        }

        return $this->wrap(function () use ($type, $className) {
            $fetchMode = $this->getFetchMode($type);

            if ($fetchMode === PDO::FETCH_CLASS && $className !== null) {
                $this->stmt->setFetchMode($fetchMode, $className);
                return $this->stmt->fetch();
            }

            return $this->stmt->fetch($fetchMode) ?: null;
        }, 'Error fetching result');
    }

    public function fetchColumn(int $idx = 0): mixed
    {
        if (!$this->stmt) {
            return null;
        }

        return $this->wrap(fn() => $this->stmt->fetchColumn($idx), 'Error fetching column');
    }

    public function lastInsertId(): string|false
    {
        return $this->wrap(fn() => $this->pdo->lastInsertId(), 'Error getting last insert ID');
    }

    public function rowCount(): int
    {
        return $this->wrap(fn() => $this->stmt?->rowCount() ?? 0, 'Error getting row count');
    }

    public function beginTransaction(): bool
    {
        return $this->wrap(fn() => $this->pdo->beginTransaction(), 'Error beginning transaction');
    }

    public function commit(): bool
    {
        return $this->wrap(fn() => $this->pdo->commit(), 'Error committing transaction');
    }

    public function inTransaction(): bool
    {
        return $this->wrap(fn() => $this->pdo->inTransaction(), 'Error checking transaction state');
    }

    public function rollBack(): bool
    {
        return $this->wrap(fn() => $this->pdo->rollBack(), 'Error rolling back transaction');
    }

    private function getFetchMode(?string $type): int
    {
        if ($type === null) {
            return $this->defaultFetch;
        }

        return match(strtolower($type)) {
            'assoc' => PDO::FETCH_ASSOC,
            'object' => PDO::FETCH_CLASS,
            'numeric' => PDO::FETCH_NUM,
            'both' => PDO::FETCH_BOTH,
            'column' => PDO::FETCH_COLUMN,
            'named' => PDO::FETCH_NAMED,
            'lazy' => PDO::FETCH_LAZY,
            default => $this->defaultFetch,
        };
    }

    private function wrap(callable $callback, string $message): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            throw new QueryBuilderException("$message: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }
}