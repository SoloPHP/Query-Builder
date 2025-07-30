<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Pool;

use Solo\QueryBuilder\Executors\PdoExecutor\{Connection, Config};
use Solo\QueryBuilder\Exception\QueryBuilderException;
use SplQueue;

final class ConnectionPool implements ConnectionPoolInterface
{
    private SplQueue $available;
    private array $inUse = [];
    private array $connectionTimestamps = [];
    private bool $closed = false;

    public function __construct(
        private readonly Config $config,
        private readonly int $maxConnections = 10,
        private readonly int $minConnections = 2,
        private readonly int $maxIdleTime = 3600, // 1 hour
        private readonly int $connectionTimeout = 30 // 30 seconds
    ) {
        if ($this->maxConnections < 1) {
            throw new QueryBuilderException('maxConnections must be at least 1');
        }
        if ($this->minConnections < 0 || $this->minConnections > $this->maxConnections) {
            throw new QueryBuilderException('minConnections must be between 0 and maxConnections');
        }
        if ($this->maxIdleTime < 1) {
            throw new QueryBuilderException('maxIdleTime must be at least 1 second');
        }
        if ($this->connectionTimeout < 1) {
            throw new QueryBuilderException('connectionTimeout must be at least 1 second');
        }

        $this->available = new SplQueue();
        $this->initializeMinConnections();

        register_shutdown_function([$this, 'closeAll']);
    }

    public function getConnection(): Connection
    {
        if ($this->closed) {
            throw new QueryBuilderException('Connection pool is closed');
        }

        $this->cleanupExpiredConnections();

        // Try to get from available pool
        if (!$this->available->isEmpty()) {
            $connection = $this->available->dequeue();
            $connectionId = spl_object_id($connection);

            if ($this->isConnectionValid($connection)) {
                $this->inUse[$connectionId] = $connection;
                unset($this->connectionTimestamps[$connectionId]);
                return $connection;
            } else {
                unset($this->connectionTimestamps[$connectionId]);
            }
        }

        // Create new connection if under limit
        if ($this->getTotalConnections() < $this->maxConnections) {
            $connection = $this->createConnection();
            $connectionId = spl_object_id($connection);
            $this->inUse[$connectionId] = $connection;
            return $connection;
        }

        // Wait for available connection
        $startTime = time();
        $sleepTime = 50000; // Начинаем с 50ms

        while (time() - $startTime < $this->connectionTimeout) {
            $this->cleanupExpiredConnections();

            if (!$this->available->isEmpty()) {
                $connection = $this->available->dequeue();
                $connectionId = spl_object_id($connection);

                if ($this->isConnectionValid($connection)) {
                    $this->inUse[$connectionId] = $connection;
                    unset($this->connectionTimestamps[$connectionId]);
                    return $connection;
                }
                unset($this->connectionTimestamps[$connectionId]);
            }

            usleep($sleepTime);
            $sleepTime = min((int)($sleepTime * 1.5), 500000);
        }

        throw new QueryBuilderException(
            "Unable to get connection from pool within {$this->connectionTimeout} seconds"
        );
    }

    public function releaseConnection(Connection $connection): void
    {
        if ($this->closed) {
            return;
        }

        $connectionId = spl_object_id($connection);

        if (!isset($this->inUse[$connectionId])) {
            return; // Connection not from this pool
        }

        unset($this->inUse[$connectionId]);

        // Check if connection is still valid
        if ($this->isConnectionValid($connection)) {
            $this->available->enqueue($connection);
            $this->connectionTimestamps[$connectionId] = time();
        }

        $this->maintainMinConnections();
    }

    public function closeAll(): void
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;

        // Clear available connections
        while (!$this->available->isEmpty()) {
            $this->available->dequeue();
        }

        // Clear in-use connections (they will be closed when released)
        $this->inUse = [];
        $this->connectionTimestamps = [];
    }

    public function getActiveConnections(): int
    {
        return count($this->inUse);
    }

    public function getIdleConnections(): int
    {
        return $this->available->count();
    }

    public function getTotalConnections(): int
    {
        return $this->getActiveConnections() + $this->getIdleConnections();
    }

    private function initializeMinConnections(): void
    {
        for ($i = 0; $i < $this->minConnections; $i++) {
            try {
                $connection = $this->createConnection();
                $this->available->enqueue($connection);
                $this->connectionTimestamps[spl_object_id($connection)] = time();
            } catch (\Throwable $e) {
                break;
            }
        }
    }

    private function maintainMinConnections(): void
    {
        $needed = $this->minConnections - $this->getTotalConnections();

        for ($i = 0; $i < $needed; $i++) {
            try {
                $connection = $this->createConnection();
                $this->available->enqueue($connection);
                $this->connectionTimestamps[spl_object_id($connection)] = time();
            } catch (\Throwable $e) {
                break;
            }
        }
    }

    private function createConnection(): Connection
    {
        return new Connection($this->config);
    }

    private function isConnectionValid(Connection $connection): bool
    {
        try {
            $pdo = $connection->pdo();
            $stmt = $pdo->query('SELECT 1');
            return $stmt !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function cleanupExpiredConnections(): void
    {
        $currentTime = time();
        $toRemove = [];

        foreach ($this->connectionTimestamps as $connectionId => $timestamp) {
            if ($currentTime - $timestamp > $this->maxIdleTime) {
                $toRemove[] = $connectionId;
            }
        }

        // Remove expired connections from available queue
        if (!empty($toRemove)) {
            $tempQueue = new SplQueue();

            while (!$this->available->isEmpty()) {
                $connection = $this->available->dequeue();
                $connectionId = spl_object_id($connection);

                if (!in_array($connectionId, $toRemove)) {
                    $tempQueue->enqueue($connection);
                } else {
                    unset($this->connectionTimestamps[$connectionId]);
                }
            }

            $this->available = $tempQueue;
            $this->maintainMinConnections();
        }
    }

    public function __destruct()
    {
        $this->closeAll();
    }
}
