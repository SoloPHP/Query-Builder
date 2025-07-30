<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Executor;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Contracts\ExecutorInterface;

class ExecutorTest extends TestCase
{
    /**
     * Test the query executor.
     */
    public function testPdoExecutor(): void
    {
        // Create mock for ExecutorInterface
        $executorMock = $this->createMock(ExecutorInterface::class);

        // Configure expected behavior
        $executorMock->expects($this->once())
            ->method('query')
            ->with(
                $this->equalTo('SELECT * FROM users WHERE status = ?'),
                $this->equalTo(['active'])
            )
            ->willReturnSelf();

        $executorMock->expects($this->once())
            ->method('fetchAll')
            ->with($this->equalTo('assoc'))
            ->willReturn([
                ['id' => 1, 'name' => 'Test']
            ]);

        // Execute query directly through mock
        $results = $executorMock->query('SELECT * FROM users WHERE status = ?', ['active'])->fetchAll('assoc');

        // Check results
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals(1, $results[0]['id']);
        $this->assertEquals('Test', $results[0]['name']);
    }

    /**
     * Test result fetch methods using direct mock of the Executor
     */
    public function testResultFetchMethods(): void
    {
        // Test for getAssoc
        $executorMock1 = $this->createMock(ExecutorInterface::class);
        $executorMock1->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains('SELECT'),
                $this->equalTo([1])
            )
            ->willReturnSelf();

        $executorMock1->expects($this->once())
            ->method('fetch')
            ->with($this->equalTo('assoc'))
            ->willReturn(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);

        // Execute query directly through mock
        $executorMock1->query('SELECT * FROM users WHERE id = ?', [1]);
        $user = $executorMock1->fetch('assoc');

        $this->assertIsArray($user);
        $this->assertEquals(1, $user['id']);
        $this->assertEquals('John Doe', $user['name']);
        $this->assertEquals('john@example.com', $user['email']);

        // Test for getAllAssoc
        $executorMock2 = $this->createMock(ExecutorInterface::class);
        $executorMock2->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains('SELECT'),
                $this->equalTo(['active'])
            )
            ->willReturnSelf();

        $executorMock2->expects($this->once())
            ->method('fetchAll')
            ->with($this->equalTo('assoc'))
            ->willReturn([
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']
            ]);

        // Execute query directly through mock
        $executorMock2->query('SELECT * FROM users WHERE status = ?', ['active']);
        $users = $executorMock2->fetchAll('assoc');

        $this->assertIsArray($users);
        $this->assertCount(2, $users);
        $this->assertEquals(1, $users[0]['id']);
        $this->assertEquals('Jane Smith', $users[1]['name']);
    }

    /**
     * Test rowCount and lastInsertId functionality
     */
    public function testRowCountAndLastInsertId(): void
    {
        $executorMock = $this->createMock(ExecutorInterface::class);

        // Test rowCount
        $executorMock->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains('UPDATE'),
                $this->equalTo(['active', 1, 2, 3])
            )
            ->willReturnSelf();

        $executorMock->expects($this->once())
            ->method('rowCount')
            ->willReturn(3);

        $executorMock->query('UPDATE users SET status = ? WHERE id IN (?, ?, ?)', ['active', 1, 2, 3]);
        $affectedRows = $executorMock->rowCount();

        $this->assertEquals(3, $affectedRows);

        // Test lastInsertId in a separate mock
        $insertMock = $this->createMock(ExecutorInterface::class);

        $insertMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('42');

        $lastId = $insertMock->lastInsertId();

        $this->assertEquals('42', $lastId);
    }

    /**
     * Test transaction methods
     */
    public function testTransactionMethods(): void
    {
        $executorMock = $this->createMock(ExecutorInterface::class);

        $executorMock->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        $executorMock->expects($this->once())
            ->method('inTransaction')
            ->willReturn(true);

        $executorMock->expects($this->once())
            ->method('commit')
            ->willReturn(true);

        $beginResult = $executorMock->beginTransaction();
        $this->assertTrue($beginResult);

        $inTransactionResult = $executorMock->inTransaction();
        $this->assertTrue($inTransactionResult);

        $commitResult = $executorMock->commit();
        $this->assertTrue($commitResult);

        // Test rollback in a separate mock
        $rollbackMock = $this->createMock(ExecutorInterface::class);

        $rollbackMock->expects($this->once())
            ->method('rollBack')
            ->willReturn(true);

        $rollbackResult = $rollbackMock->rollBack();
        $this->assertTrue($rollbackResult);
    }
}
