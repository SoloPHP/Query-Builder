<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Facade;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Factory\BuilderFactory;
use Solo\QueryBuilder\Factory\GrammarFactory;

class QueryTest extends TestCase
{
    private Query $query;

    protected function setUp(): void
    {
        $executor = $this->createMock(ExecutorInterface::class);
        $grammarFactory = new GrammarFactory();
        $builderFactory = new BuilderFactory($grammarFactory, $executor, 'mysql');
        Query::disableCache();
        $this->query = new Query($builderFactory);
    }

    public function testSelectBuild(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('id', 'name')
            ->where('status = ?', 'active')
            ->orderBy('id', 'ASC')
            ->limit(5)
            ->build();

        $this->assertStringContainsString('SELECT `id`, `name` FROM `users`', $sql);
        $this->assertStringContainsString('ORDER BY `id` ASC', $sql); // Updated assertion
        $this->assertEquals(['active'], $bindings);
    }

    public function testInsertBuild(): void
    {
        [$sql, $bindings] = $this->query
            ->insert('users')
            ->values(['name' => 'Bob'])
            ->build();

        $this->assertStringContainsString('INSERT INTO `users`', $sql);
        $this->assertEquals(['Bob'], $bindings);
    }

    public function testUpdateBuild(): void
    {
        [$sql, $bindings] = $this->query
            ->update('users')
            ->set('name', 'Alice')
            ->where('id = ?', 2)
            ->build();

        $this->assertStringContainsString('UPDATE `users` SET `name` = ?', $sql);
        $this->assertEquals(['Alice', 2], $bindings);
    }

    public function testDeleteBuild(): void
    {
        [$sql, $bindings] = $this->query
            ->delete('users')
            ->where('id = ?', 3)
            ->build();

        $this->assertStringContainsString('DELETE FROM `users`', $sql);
        $this->assertEquals([3], $bindings);
    }
}
