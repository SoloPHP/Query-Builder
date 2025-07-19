<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Capability;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Factory\BuilderFactory;
use Solo\QueryBuilder\Factory\GrammarFactory;

/**
 * Tests for the GroupByTrait capability.
 */
class GroupByTraitTest extends TestCase
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
     * Test basic groupBy functionality.
     */
    public function testBasicGroupBy(): void
    {
        [$sql, $bindings] = $this->query
            ->from('orders')
            ->select('user_id', '{COUNT(*) as order_count}')
            ->groupBy('user_id')
            ->build();

        $this->assertStringContainsString('GROUP BY `user_id`', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test groupBy with multiple columns.
     */
    public function testGroupByMultipleColumns(): void
    {
        [$sql, $bindings] = $this->query
            ->from('sales')
            ->select('region', 'product', '{SUM(amount) as total_sales}')
            ->groupBy('region', 'product')
            ->build();

        $this->assertStringContainsString('GROUP BY `region`, `product`', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test groupBy with raw expressions.
     */
    public function testGroupByWithRawExpressions(): void
    {
        [$sql, $bindings] = $this->query
            ->from('orders')
            ->select('{DATE_FORMAT(created_at, "%Y-%m") as month}', '{COUNT(*) as order_count}')
            ->groupBy('{DATE_FORMAT(created_at, "%Y-%m")}')
            ->build();

        $this->assertStringContainsString('GROUP BY DATE_FORMAT(created_at, "%Y-%m")', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test groupBy combined with having.
     */
    public function testGroupByWithHaving(): void
    {
        [$sql, $bindings] = $this->query
            ->from('orders')
            ->select('user_id', '{COUNT(*) as order_count}')
            ->groupBy('user_id')
            ->having('order_count > ?', 5)
            ->build();

        $this->assertStringContainsString('GROUP BY `user_id`', $sql);
        $this->assertStringContainsString('HAVING order_count > ?', $sql);
        $this->assertEquals([5], $bindings);
    }

    /**
     * Test groupBy with ordered results.
     */
    public function testGroupByWithOrderBy(): void
    {
        [$sql, $bindings] = $this->query
            ->from('products')
            ->select('category_id', '{AVG(price) as avg_price}')
            ->groupBy('category_id')
            ->orderBy('avg_price', 'DESC')
            ->build();

        $this->assertStringContainsString('GROUP BY `category_id`', $sql);
        $this->assertStringContainsString('ORDER BY `avg_price` DESC', $sql);
        $this->assertEquals([], $bindings);
    }
}
