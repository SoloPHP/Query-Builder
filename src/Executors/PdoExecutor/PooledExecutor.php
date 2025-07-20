<?php declare(strict_types=1);

namespace Solo\QueryBuilder\Executors\PdoExecutor;

use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Pool\ConnectionPoolInterface;
use Solo\QueryBuilder\Exception\QueryBuilderException;
use PDO;
use PDOStatement;
use Throwable;

final class PooledExecutor implements ExecutorInterface
{
    private ?Connection $currentConnection = null;
    private ?PDOStatement $stmt = null;
    private bool $inTransaction = false;

    public function __construct(
        private readonly ConnectionPoolInterface $pool,
        private int $defaultFetch = PDO::FETCH_ASSOC
    ) {}

    public function query(string $sql, array $bindings = []): self
    {
        return $this->wrap(function () use ($sql, $bindings) {
            $this->ensureConnection();
            $pdo = $this->currentConnection->pdo();

            $this->stmt = $pdo->prepare($sql);
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
                return $this->stmt->fetch() ?: null;
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
        $this->ensureConnection();
        return $this->wrap(
            fn() => $this->currentConnection->pdo()->lastInsertId(),
            'Error getting last insert ID'
        );
    }

    public function rowCount(): int
    {
        return $this->wrap(fn() => $this->stmt?->rowCount() ?? 0, 'Error getting row count');
    }

    public function beginTransaction(): bool
    {
        $this->ensureConnection();
        $result = $this->wrap(
            fn() => $this->currentConnection->pdo()->beginTransaction(),
            'Error beginning transaction'
        );

        if ($result) {
            $this->inTransaction = true;
        }

        return $result;
    }

    public function commit(): bool
    {
        $this->ensureConnection();
        $result = $this->wrap(
            fn() => $this->currentConnection->pdo()->commit(),
            'Error committing transaction'
        );

        if ($result) {
            $this->inTransaction = false;
            $this->releaseConnection();
        }

        return $result;
    }

    public function inTransaction(): bool
    {
        if (!$this->currentConnection) {
            return $this->inTransaction;
        }

        return $this->wrap(
            fn() => $this->currentConnection->pdo()->inTransaction(),
            'Error checking transaction state'
        );
    }

    public function rollBack(): bool
    {
        $this->ensureConnection();
        $result = $this->wrap(
            fn() => $this->currentConnection->pdo()->rollBack(),
            'Error rolling back transaction'
        );

        if ($result) {
            $this->inTransaction = false;
            $this->releaseConnection();
        }

        return $result;
    }

    public function __destruct()
    {
        if ($this->inTransaction && $this->currentConnection) {
            try {
                $this->currentConnection->pdo()->rollBack();
            } catch (Throwable) {
                // Ignore errors in destructor
            }
        }

        $this->releaseConnection();
    }

    private function ensureConnection(): void
    {
        if (!$this->currentConnection) {
            $this->currentConnection = $this->pool->getConnection();
            $this->defaultFetch = $this->currentConnection->fetchMode();
        }
    }

    private function releaseConnection(): void
    {
        if ($this->currentConnection && !$this->inTransaction) {
            $this->pool->releaseConnection($this->currentConnection);
            $this->currentConnection = null;
            $this->stmt = null;
        }
    }

    private function getFetchMode(?string $type): int
    {
        if ($type === null) {
            return $this->defaultFetch;
        }

        return match (strtolower($type)) {
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

    public function getConnectionPool(): ConnectionPoolInterface
    {
        return $this->pool;
    }
}