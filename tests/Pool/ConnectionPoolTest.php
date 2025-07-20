<?php declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Pool;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Pool\ConnectionPool;
use Solo\QueryBuilder\Executors\PdoExecutor\{Connection, Config};
use Solo\QueryBuilder\Exception\QueryBuilderException;

class ConnectionPoolTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        $this->config = new Config(
            host: ':memory:',
            user: '',
            pass: '',
            db: ':memory:',
            driver: 'sqlite'
        );
    }

    public function testBasicPoolOperations(): void
    {
        $pool = new ConnectionPool(
            config: $this->config,
            maxConnections: 5,
            minConnections: 2
        );

        $this->assertEquals(2, $pool->getTotalConnections());
        $this->assertEquals(0, $pool->getActiveConnections());
        $this->assertEquals(2, $pool->getIdleConnections());

        $connection = $pool->getConnection();
        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertEquals(1, $pool->getActiveConnections());

        $pool->releaseConnection($connection);
        $this->assertEquals(0, $pool->getActiveConnections());
        $this->assertGreaterThanOrEqual(2, $pool->getIdleConnections());
    }

    public function testPoolLimits(): void
    {
        $pool = new ConnectionPool(
            config: $this->config,
            maxConnections: 2,
            minConnections: 1,
            connectionTimeout: 1
        );

        $conn1 = $pool->getConnection();
        $conn2 = $pool->getConnection();

        $this->expectException(QueryBuilderException::class);
        $pool->getConnection();
    }

    public function testParameterValidation(): void
    {
        $this->expectException(QueryBuilderException::class);

        new ConnectionPool(
            config: $this->config,
            maxConnections: 0
        );
    }

    public function testPoolClosure(): void
    {
        $pool = new ConnectionPool(
            config: $this->config,
            maxConnections: 5,
            minConnections: 2
        );

        $pool->closeAll();
        $this->assertEquals(0, $pool->getTotalConnections());

        $this->expectException(QueryBuilderException::class);
        $pool->getConnection();
    }
}