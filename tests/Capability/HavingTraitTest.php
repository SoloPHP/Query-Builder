<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Capability;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Factory\BuilderFactory;
use Solo\QueryBuilder\Factory\GrammarFactory;

/**
 * Tests for the HavingTrait capability.
 */
class HavingTraitTest extends TestCase
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
     * Test basic having functionality.
     */
    public function testBasicHaving(): void
    {
        [$sql, $bindings] = $this->query
            ->from('orders')
            ->select('user_id', '{COUNT(*) as order_count}')
            ->groupBy('user_id')
            ->having('order_count > ?', 5)
            ->build();

        $this->assertStringContainsString('HAVING order_count > ?', $sql);
        $this->assertEquals([5], $bindings);
    }

    /**
     * Test having with multiple conditions using AND.
     */
    public function testHavingWithMultipleConditionsUsingAnd(): void
    {
        [$sql, $bindings] = $this->query
            ->from('orders')
            ->select('user_id', '{COUNT(*) as order_count}', '{SUM(amount) as total_amount}')
            ->groupBy('user_id')
            ->having('order_count > ?', 5)
            ->having('total_amount > ?', 1000)
            ->build();

        $this->assertStringContainsString('HAVING order_count > ? AND total_amount > ?', $sql);
        $this->assertEquals([5, 1000], $bindings);
    }

    /**
     * Test having with multiple conditions using OR.
     */
    public function testHavingWithMultipleConditionsUsingOr(): void
    {
        [$sql, $bindings] = $this->query
            ->from('orders')
            ->select('user_id', '{COUNT(*) as order_count}', '{SUM(amount) as total_amount}')
            ->groupBy('user_id')
            ->having('order_count > ?', 10)
            ->orHaving('total_amount > ?', 2000)
            ->build();

        $this->assertStringContainsString('HAVING order_count > ? OR total_amount > ?', $sql);
        $this->assertEquals([10, 2000], $bindings);
    }

    /**
     * Test having with a raw expression.
     */
    public function testHavingWithRawExpression(): void
    {
        [$sql, $bindings] = $this->query
            ->from('products')
            ->select('category_id', '{AVG(price) as avg_price}')
            ->groupBy('category_id')
            ->having('{AVG(price) < ?}', 100)
            ->build();

        $this->assertStringContainsString('HAVING AVG(price) < ?', $sql);
        $this->assertEquals([100], $bindings);
    }

    /**
     * Test having with complex nested conditions.
     */
    public function testHavingWithNestedConditions(): void
    {
        [$sql, $bindings] = $this->query
            ->from('orders')
            ->select('user_id', '{COUNT(*) as order_count}', '{SUM(amount) as total_amount}')
            ->groupBy('user_id')
            ->having(function ($condition) {
                $condition->where('order_count > ?', 5)
                    ->orWhere('total_amount > ?', 1000);
            })
            ->build();

        $this->assertStringContainsString('HAVING (order_count > ? OR total_amount > ?)', $sql);
        $this->assertEquals([5, 1000], $bindings);
    }

    /**
     * Test havingIn method.
     */
    public function testHavingIn(): void
    {
        [$sql, $bindings] = $this->query
            ->from('orders')
            ->select('user_id', '{COUNT(*) as order_count}')
            ->groupBy('user_id')
            ->havingIn('user_id', [1, 2, 3])
            ->build();

        $this->assertStringContainsString('HAVING user_id IN (?, ?, ?)', $sql);
        $this->assertEquals([1, 2, 3], $bindings);
    }

    /**
     * Test andHavingIn method.
     */
    public function testAndHavingIn(): void
    {
        [$sql, $bindings] = $this->query
            ->from('orders')
            ->select('user_id', '{COUNT(*) as order_count}')
            ->groupBy('user_id')
            ->having('order_count > ?', 5)
            ->andHavingIn('user_id', [1, 2, 3])
            ->build();

        $this->assertStringContainsString('HAVING order_count > ? AND user_id IN (?, ?, ?)', $sql);
        $this->assertEquals([5, 1, 2, 3], $bindings);
    }

    /**
     * Test orHavingIn method.
     */
    public function testOrHavingIn(): void
    {
        [$sql, $bindings] = $this->query
            ->from('orders')
            ->select('user_id', '{COUNT(*) as order_count}')
            ->groupBy('user_id')
            ->having('order_count > ?', 5)
            ->orHavingIn('user_id', [1, 2, 3])
            ->build();

        $this->assertStringContainsString('HAVING order_count > ? OR user_id IN (?, ?, ?)', $sql);
        $this->assertEquals([5, 1, 2, 3], $bindings);
    }
}
