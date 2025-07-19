<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Capability;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Factory\BuilderFactory;
use Solo\QueryBuilder\Factory\GrammarFactory;

/**
 * Tests for the DISTINCT functionality.
 */
class DistinctTest extends TestCase
{
    private Query $query;

    protected function setUp(): void
    {
        $mockExecutor = $this->createMock(ExecutorInterface::class);
        $grammarFactory = new GrammarFactory();
        $builderFactory = new BuilderFactory($grammarFactory, $mockExecutor, 'mysql');
        $this->query = new Query($builderFactory);
    }

    /**
     * Test basic distinct select functionality.
     */
    public function testBasicDistinct(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('city')
            ->distinct()
            ->build();

        $this->assertStringContainsString('SELECT DISTINCT', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test distinct with multiple columns.
     */
    public function testDistinctWithMultipleColumns(): void
    {
        [$sql, $bindings] = $this->query
            ->from('orders')
            ->select('user_id', 'status')
            ->distinct()
            ->build();

        $this->assertStringContainsString('SELECT DISTINCT `user_id`, `status`', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test distinct with where conditions.
     */
    public function testDistinctWithWhereCondition(): void
    {
        [$sql, $bindings] = $this->query
            ->from('products')
            ->select('category_id')
            ->distinct()
            ->where('price > ?', 100)
            ->build();

        $this->assertStringContainsString('SELECT DISTINCT `category_id` FROM `products` WHERE price > ?', $sql);
        $this->assertEquals([100], $bindings);
    }

    /**
     * Test turning off distinct.
     */
    public function testDistinctTurnOff(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('city')
            ->distinct()  // Enable DISTINCT
            ->distinct(false)  // Disable DISTINCT
            ->build();

        $this->assertStringNotContainsString('DISTINCT', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test distinct with joins.
     */
    public function testDistinctWithJoin(): void
    {
        [$sql, $bindings] = $this->query
            ->from('orders')
            ->select('users.name')
            ->distinct()
            ->join('users', 'orders.user_id = users.id')
            ->build();

        $this->assertStringContainsString('SELECT DISTINCT `users`.`name`', $sql);
        $this->assertStringContainsString('INNER JOIN `users` ON', $sql);
        $this->assertEquals([], $bindings);
    }
}
