<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Utility;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Utility\QueryFactory;
use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Executors\PdoExecutor\PdoExecutor;

class QueryFactoryTest extends TestCase
{
    public function testCreateWithPdo(): void
    {
        $query = QueryFactory::createWithPdo(
            host: ':memory:',
            username: '',
            password: '',
            database: ':memory:',
            dbType: 'sqlite'
        );

        $this->assertInstanceOf(Query::class, $query);
        $this->assertInstanceOf(PdoExecutor::class, $query->getExecutor());
    }

    public function testCreateWithConnectionPool(): void
    {
        $query = QueryFactory::createWithConnectionPool(
            host: ':memory:',
            username: '',
            password: '',
            database: ':memory:',
            dbType: 'sqlite',
            maxConnections: 5,
            minConnections: 2
        );

        $this->assertInstanceOf(Query::class, $query);

        $pool = $query->getConnectionPool();
        $this->assertNotNull($pool);
        $this->assertEquals(2, $pool->getTotalConnections());
    }

    public function testQueryFunctionality(): void
    {
        $query = QueryFactory::createWithPdo(
            host: ':memory:',
            username: '',
            password: '',
            database: ':memory:',
            dbType: 'sqlite'
        );

        $query->getExecutor()->query('CREATE TABLE test (id INTEGER, name TEXT)');

        $affected = $query->insert('test')
            ->values(['id' => 1, 'name' => 'Test'])
            ->execute();
        $this->assertEquals(1, $affected);

        $result = $query->from('test')->where('id = ?', 1)->getAssoc();
        $this->assertEquals(['id' => 1, 'name' => 'Test'], $result);
    }
}
