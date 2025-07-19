<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Executors\PdoExecutor;

use PDO;

final readonly class Config
{
    private const DSN = [
        'mysql' => 'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        'pgsql' => 'pgsql:host=%s;port=%d;dbname=%s',
        'sqlite' => 'sqlite:%s',
    ];

    private const DEFAULT_PORTS = [
        'mysql' => 3306,
        'pgsql' => 5432,
        'sqlite' => 0,
    ];

    public function __construct(
        private string $host,
        private string $user,
        private string $pass,
        private string $db,
        private int $fetchMode = PDO::FETCH_ASSOC,
        private string $driver = 'mysql',
        private ?int $port = null,
        private array $options = [],
    ) {
    }

    public function dsn(): string
    {
        $driver = strtolower($this->driver);
        $port = $this->port ?? self::DEFAULT_PORTS[$driver] ?? self::DEFAULT_PORTS['mysql'];

        if ($driver === 'sqlite') {
            return sprintf(self::DSN[$driver], $this->db);
        }

        return sprintf(self::DSN[$driver], $this->host, $port, $this->db);
    }

    public function username(): string
    {
        return $this->user;
    }

    public function password(): string
    {
        return $this->pass;
    }

    public function options(): array
    {
        $opt = $this->options + [PDO::ATTR_DEFAULT_FETCH_MODE => $this->fetchMode];

        if (strtolower($this->driver) === 'mysql') {
            $opt += [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            ];
        } else {
            $opt += [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        }

        return $opt;
    }

    public function fetchMode(): int
    {
        return $this->fetchMode;
    }
}
