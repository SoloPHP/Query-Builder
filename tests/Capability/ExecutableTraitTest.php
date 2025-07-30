<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Capability;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Factory\BuilderFactory;
use Solo\QueryBuilder\Factory\GrammarFactory;

/**
 * Tests for the ExecutableTrait capability.
 */
class ExecutableTraitTest extends TestCase
{
    private Query $query;
    private ExecutorInterface $mockExecutor;

    protected function setUp(): void
    {
        $this->mockExecutor = $this->createMock(ExecutorInterface::class);
        $grammarFactory = new GrammarFactory();
        $builderFactory = new BuilderFactory($grammarFactory, $this->mockExecutor, 'mysql');
        $this->query = new Query($builderFactory);
    }

    /**
     * Test execute method for UPDATE queries.
     */
    public function testExecuteUpdate(): void
    {
        $this->mockExecutor->expects($this->once())
            ->method('query')
            ->with(
                $this->callback(function ($sql) {
                    $this->assertStringContainsString('UPDATE `users` SET `status` = ?', $sql);
                    return true;
                }),
                $this->equalTo(['active', 1])
            )
            ->willReturnSelf();

        $this->mockExecutor->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        $result = $this->query
            ->update('users')
            ->set('status', 'active')
            ->where('id = ?', 1)
            ->execute();

        $this->assertSame(1, $result);
    }

    /**
     * Test execute method for DELETE queries.
     */
    public function testExecuteDelete(): void
    {
        $this->mockExecutor->expects($this->once())
            ->method('query')
            ->with(
                $this->callback(function ($sql) {
                    return strpos($sql, 'DELETE FROM `users`') !== false;
                }),
                $this->equalTo([1])
            )
            ->willReturnSelf();

        $this->mockExecutor->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        $result = $this->query
            ->delete('users')
            ->where('id = ?', 1)
            ->execute();

        $this->assertEquals(1, $result);
    }

    /**
     * Test execute method for INSERT queries.
     */
    public function testExecuteInsert(): void
    {
        $this->mockExecutor->expects($this->once())
            ->method('query')
            ->with(
                $this->callback(function ($sql) {
                    return strpos($sql, 'INSERT INTO `users`') !== false;
                }),
                $this->equalTo(['John Doe', 'john@example.com'])
            )
            ->willReturnSelf();

        $this->mockExecutor->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        $result = $this->query
            ->insert('users')
            ->values([
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ])
            ->execute();

        $this->assertEquals(1, $result);
    }

    /**
     * Test execute method with batch operations.
     */
    public function testExecuteBatchOperation(): void
    {
        $this->mockExecutor->expects($this->once())
            ->method('query')
            ->with(
                $this->callback(function ($sql) {
                    $this->assertStringContainsString('UPDATE `users` SET `status` = ?', $sql);
                    return true;
                }),
                $this->equalTo(['inactive', 10])
            )
            ->willReturnSelf();

        $this->mockExecutor->expects($this->once())
            ->method('rowCount')
            ->willReturn(5);

        $result = $this->query
            ->update('users')
            ->set('status', 'inactive')
            ->where('last_login < ?', 10)
            ->execute();

        $this->assertSame(5, $result);
    }

    /**
     * Test exception when no executor is available.
     */
    public function testExceptionWhenNoExecutor(): void
    {
        // Create a query without an executor
        $grammarFactory = new GrammarFactory();
        $compiler = new \Solo\QueryBuilder\Compiler\SqlCompiler($grammarFactory->create('mysql'));
        $builder = new \Solo\QueryBuilder\Builder\UpdateBuilder('users', $compiler); // No executor passed

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No executor available to execute the query');

        $builder
            ->set('name', 'John Doe')
            ->execute();
    }
}
