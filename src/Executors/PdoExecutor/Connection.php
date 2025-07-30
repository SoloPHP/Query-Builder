<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Executors\PdoExecutor;

use PDO;
use PDOException;
use Solo\QueryBuilder\Exception\QueryBuilderException;

final class Connection
{
    private PDO $pdo;
    private int $fetchMode;

    public function __construct(Config $cfg)
    {
        $this->fetchMode = $cfg->fetchMode();

        try {
            $this->pdo = new PDO(
                $cfg->dsn(),
                $cfg->username(),
                $cfg->password(),
                $cfg->options()
            );
        } catch (PDOException $e) {
            throw new QueryBuilderException(
                'Database connection failed: ' . $e->getMessage(),
                (int)$e->getCode(),
                $e
            );
        }
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function fetchMode(): int
    {
        return $this->fetchMode;
    }
}
