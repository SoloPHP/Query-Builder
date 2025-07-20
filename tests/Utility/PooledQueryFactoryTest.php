<?php declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Utility;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Utility\PooledQueryFactory;
use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Executors\PdoExecutor\{PooledExecutor, Config};
use Solo\QueryBuilder\Pool\{ConnectionPool, ConnectionPoolInterface};

class PooledQueryFactoryTest extends TestCase
{
    public function testCreateWithPool(): void
    {
        $query = PooledQueryFactory::createWithPool(
            host: ':memory:',
            username: '',
            password: '',
            database: ':memory:',
            dbType: 'sqlite',
            maxConnections: 5,
            minConnections: 2
        );

        $this->assertInstanceOf(Query::class, $query);
        $this->assertInstanceOf(PooledExecutor::class, $query->getExecutor());

        $pool = $query->getConnectionPool();
        $this->assertInstanceOf(ConnectionPoolInterface::class, $pool);
        $this->assertEquals(2, $pool->getTotalConnections());
    }

    public function testCreatePool(): void
    {
        $config = new Config(
            host: ':memory:',
            user: '',
            pass: '',
            db: ':memory:',
            driver: 'sqlite'
        );

        $pool = PooledQueryFactory::createPool(
            config: $config,
            maxConnections: 8,
            minConnections: 3
        );

        $this->assertInstanceOf(ConnectionPool::class, $pool);
        $this->assertEquals(3, $pool->getTotalConnections());
        $this->assertEquals(0, $pool->getActiveConnections());
        $this->assertEquals(3, $pool->getIdleConnections());
    }

    public function testPooledQueryFunctionality(): void
    {
        $query = PooledQueryFactory::createWithPool(
            host: ':memory:',
            username: '',
            password: '',
            database: ':memory:',
            dbType: 'sqlite'
        );

        $query->getExecutor()->query('CREATE TABLE users (id INTEGER, name TEXT)');

        $affected = $query->insert('users')
            ->values(['id' => 1, 'name' => 'John'])
            ->execute();
        $this->assertEquals(1, $affected);

        $user = $query->from('users')->where('id = ?', 1)->getAssoc();
        $this->assertEquals(['id' => 1, 'name' => 'John'], $user);
    }
}