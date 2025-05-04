<?php declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Capability;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Factory\BuilderFactory;
use Solo\QueryBuilder\Factory\GrammarFactory;

/**
 * Tests for the JoinTrait capability.
 */
class JoinTraitTest extends TestCase
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
     * Test basic inner join functionality.
     */
    public function testBasicInnerJoin(): void
    {
        [$sql, $bindings] = $this->query
            ->from('orders')
            ->select('orders.id', 'users.name')
            ->join('users', 'orders.user_id = users.id')
            ->build();

        $this->assertStringContainsString('INNER JOIN `users` ON `orders`.`user_id` = `users`.`id`', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test left join functionality.
     */
    public function testLeftJoin(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('users.id', 'profiles.bio')
            ->leftJoin('profiles', 'users.id = profiles.user_id')
            ->build();

        $this->assertStringContainsString('LEFT JOIN `profiles` ON `users`.`id` = `profiles`.`user_id`', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test right join functionality.
     */
    public function testRightJoin(): void
    {
        [$sql, $bindings] = $this->query
            ->from('orders')
            ->select('orders.id', 'users.name')
            ->rightJoin('users', 'orders.user_id = users.id')
            ->build();

        $this->assertStringContainsString('RIGHT JOIN `users` ON `orders`.`user_id` = `users`.`id`', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test full outer join functionality.
     */
    public function testFullJoin(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('users.id', 'profiles.bio')
            ->fullJoin('profiles', 'users.id = profiles.user_id')
            ->build();

        $this->assertStringContainsString('FULL OUTER JOIN `profiles` ON `users`.`id` = `profiles`.`user_id`', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test join with additional conditions.
     */
    public function testJoinWithAdditionalConditions(): void
    {
        [$sql, $bindings] = $this->query
            ->from('orders')
            ->join('users', 'orders.user_id = users.id AND users.status = ?', 'active')
            ->select('orders.id', 'users.name')
            ->build();

        $this->assertStringContainsString('ON `orders`.`user_id` = `users`.`id` AND `users`.`status` = ?', $sql);
        $this->assertEquals(['active'], $bindings);
    }

    /**
     * Test joinSub functionality (joining with a subquery).
     */
    public function testJoinSub(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('users.id', 'users.name', 'order_counts.count')
            ->joinSub(function ($query) {
                $query->from('orders')
                    ->select('user_id', '{COUNT(*) as count}')
                    ->groupBy('user_id');
            }, 'order_counts', 'users.id = order_counts.user_id')
            ->build();

        $this->assertStringContainsString('INNER JOIN (SELECT', $sql);
        $this->assertStringContainsString('`user_id`, COUNT(*) as count FROM', $sql);
        $this->assertStringContainsString('GROUP BY `user_id`', $sql);
        $this->assertStringContainsString(') AS `order_counts` ON `users`.`id` = `order_counts`.`user_id`', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test multiple joins in a single query.
     */
    public function testMultipleJoins(): void
    {
        [$sql, $bindings] = $this->query
            ->from('orders')
            ->select('orders.id', 'users.name', 'products.title')
            ->join('users', 'orders.user_id = users.id')
            ->join('order_items', 'orders.id = order_items.order_id')
            ->join('products', 'order_items.product_id = products.id')
            ->build();

        $this->assertStringContainsString('INNER JOIN `users` ON `orders`.`user_id` = `users`.`id`', $sql);
        $this->assertStringContainsString('INNER JOIN `order_items` ON `orders`.`id` = `order_items`.`order_id`', $sql);
        $this->assertStringContainsString('INNER JOIN `products` ON `order_items`.`product_id` = `products`.`id`', $sql);
        $this->assertEquals([], $bindings);
    }
}