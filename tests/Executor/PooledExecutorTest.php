<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Executor;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Executors\PdoExecutor\{PooledExecutor, Config};
use Solo\QueryBuilder\Pool\ConnectionPool;

class PooledExecutorTest extends TestCase
{
    private PooledExecutor $executor;

    protected function setUp(): void
    {
        $config = new Config(
            host: ':memory:',
            user: '',
            pass: '',
            db: ':memory:',
            driver: 'sqlite'
        );

        $pool = new ConnectionPool(config: $config, maxConnections: 3, minConnections: 1);
        $this->executor = new PooledExecutor($pool);
    }

    public function testBasicQueryExecution(): void
    {
        $this->executor->query('CREATE TABLE test (id INTEGER, name TEXT)');
        $this->executor->query('INSERT INTO test VALUES (1, "Test")');

        $result = $this->executor->query('SELECT * FROM test')->fetch('assoc');
        $this->assertEquals(['id' => 1, 'name' => 'Test'], $result);
    }

    public function testTransactionCommit(): void
    {
        $this->executor->query('CREATE TABLE test (id INTEGER)');

        $this->assertTrue($this->executor->beginTransaction());
        $this->assertTrue($this->executor->inTransaction());

        $this->executor->query('INSERT INTO test VALUES (1)');
        $this->assertTrue($this->executor->commit());

        $this->assertFalse($this->executor->inTransaction());

        $count = $this->executor->query('SELECT COUNT(*) FROM test')->fetchColumn(0);
        $this->assertEquals(1, $count);
    }

    public function testTransactionRollback(): void
    {
        $this->executor->query('CREATE TABLE test (id INTEGER)');

        $this->assertTrue($this->executor->beginTransaction());
        $this->executor->query('INSERT INTO test VALUES (1)');
        $this->assertTrue($this->executor->rollBack());

        $this->assertFalse($this->executor->inTransaction());

        $count = $this->executor->query('SELECT COUNT(*) FROM test')->fetchColumn(0);
        $this->assertEquals(0, $count);
    }
}
