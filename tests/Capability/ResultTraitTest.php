<?php declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Capability;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Factory\BuilderFactory;
use Solo\QueryBuilder\Factory\GrammarFactory;

/**
 * Tests for the ResultTrait capability.
 */
class ResultTraitTest extends TestCase
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
     * Test getting a single associative array result.
     */
    public function testGetAssoc(): void
    {
        $resultData = ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'];

        $this->mockExecutor->method('query')->willReturnSelf();
        $this->mockExecutor->method('fetch')
            ->with('assoc')
            ->willReturn($resultData);

        $result = $this->query
            ->from('users')
            ->where('id = ?', 1)
            ->getAssoc();

        $this->assertEquals($resultData, $result);
    }

    /**
     * Test getting all results as associative arrays.
     */
    public function testGetAllAssoc(): void
    {
        $resultData = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']
        ];

        $this->mockExecutor->method('query')->willReturnSelf();
        $this->mockExecutor->method('fetchAll')
            ->with('assoc')
            ->willReturn($resultData);

        $results = $this->query
            ->from('users')
            ->where('status = ?', 'active')
            ->getAllAssoc();

        $this->assertEquals($resultData, $results);
    }

    /**
     * Test getting a single object result.
     */
    public function testGetObj(): void
    {
        $resultObject = new \stdClass();
        $resultObject->id = 1;
        $resultObject->name = 'John Doe';
        $resultObject->email = 'john@example.com';

        $this->mockExecutor->method('query')->willReturnSelf();
        $this->mockExecutor->method('fetch')
            ->with('object', 'stdClass')
            ->willReturn($resultObject);

        $result = $this->query
            ->from('users')
            ->where('id = ?', 1)
            ->getObj();

        $this->assertEquals($resultObject, $result);
    }

    /**
     * Test getting all results as objects.
     */
    public function testGetAllObj(): void
    {
        $obj1 = new \stdClass();
        $obj1->id = 1;
        $obj1->name = 'John Doe';
        $obj1->email = 'john@example.com';

        $obj2 = new \stdClass();
        $obj2->id = 2;
        $obj2->name = 'Jane Smith';
        $obj2->email = 'jane@example.com';

        $resultData = [$obj1, $obj2];

        $this->mockExecutor->method('query')->willReturnSelf();
        $this->mockExecutor->method('fetchAll')
            ->with('object', 'stdClass')
            ->willReturn($resultData);

        $results = $this->query
            ->from('users')
            ->where('status = ?', 'active')
            ->getAllObj();

        $this->assertEquals($resultData, $results);
    }

    /**
     * Test getting all results as a custom class objects.
     */
    public function testGetAllObjWithCustomClass(): void
    {
        // Define a custom user class for this test
        $user1 = new class {
            public $id = 1;
            public $name = 'John Doe';
            public $email = 'john@example.com';
        };

        $user2 = new class {
            public $id = 2;
            public $name = 'Jane Smith';
            public $email = 'jane@example.com';
        };

        $resultData = [$user1, $user2];
        $customClassName = get_class($user1);

        $this->mockExecutor->method('query')->willReturnSelf();
        $this->mockExecutor->method('fetchAll')
            ->with('object', $customClassName)
            ->willReturn($resultData);

        $results = $this->query
            ->from('users')
            ->where('status = ?', 'active')
            ->getAllObj($customClassName);

        $this->assertEquals($resultData, $results);
    }

    /**
     * Test getting a single value.
     */
    public function testGetValue(): void
    {
        $this->mockExecutor->method('query')->willReturnSelf();
        $this->mockExecutor->method('fetchColumn')
            ->with(0)
            ->willReturn('John Doe');

        $result = $this->query
            ->from('users')
            ->select('name')
            ->where('id = ?', 1)
            ->getValue();

        $this->assertEquals('John Doe', $result);
    }

    /**
     * Test getting a column of values.
     */
    public function testGetColumn(): void
    {
        $resultData = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com']
        ];

        $this->mockExecutor->method('query')->willReturnSelf();
        $this->mockExecutor->method('fetchAll')
            ->with('assoc')
            ->willReturn($resultData);

        $names = $this->query
            ->from('users')
            ->select('id', 'name', 'email')
            ->where('status = ?', 'active')
            ->getColumn('name');

        $expected = ['John Doe', 'Jane Smith', 'Bob Johnson'];
        $this->assertEquals($expected, $names);
    }

    /**
     * Test getting a column of values with keys from another column.
     */
    public function testGetColumnWithKeys(): void
    {
        $resultData = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com']
        ];

        $this->mockExecutor->method('query')->willReturnSelf();
        $this->mockExecutor->method('fetchAll')
            ->with('assoc')
            ->willReturn($resultData);

        $emails = $this->query
            ->from('users')
            ->select('id', 'name', 'email')
            ->where('status = ?', 'active')
            ->getColumn('email', 'id');

        $expected = [
            1 => 'john@example.com',
            2 => 'jane@example.com',
            3 => 'bob@example.com'
        ];
        $this->assertEquals($expected, $emails);
    }

    /**
     * Test checking if records exist.
     */
    public function testExists(): void
    {
        $this->mockExecutor->method('query')->willReturnSelf();
        $this->mockExecutor->method('fetchColumn')
            ->willReturn(5);

        $exists = $this->query
            ->from('users')
            ->where('status = ?', 'active')
            ->exists();

        $this->assertTrue($exists);
    }

    /**
     * Test checking if records do not exist.
     */
    public function testNotExists(): void
    {
        $this->mockExecutor->method('query')->willReturnSelf();
        $this->mockExecutor->method('fetchColumn')
            ->willReturn(0);

        $exists = $this->query
            ->from('users')
            ->where('status = ?', 'inactive')
            ->exists();

        $this->assertFalse($exists);
    }

    /**
     * Test counting records.
     */
    public function testCount(): void
    {
        $this->mockExecutor->method('query')->willReturnSelf();
        $this->mockExecutor->method('fetchColumn')
            ->willReturn(10);

        $count = $this->query
            ->from('users')
            ->where('status = ?', 'active')
            ->count();

        $this->assertEquals(10, $count);
    }

    /**
     * Test counting specific field.
     */
    public function testCountField(): void
    {
        $this->mockExecutor->method('query')->willReturnSelf();
        $this->mockExecutor->method('fetchColumn')
            ->willReturn(8);

        $count = $this->query
            ->from('users')
            ->where('status = ?', 'active')
            ->count('email');

        $this->assertEquals(8, $count);
    }

    /**
     * Test counting distinct values.
     */
    public function testCountDistinct(): void
    {
        $this->mockExecutor->method('query')->willReturnSelf();
        $this->mockExecutor->method('fetchColumn')
            ->willReturn(5);

        $count = $this->query
            ->from('users')
            ->where('status = ?', 'active')
            ->count('department', true);

        $this->assertEquals(5, $count);
    }

    /**
     * Test cache functionality within fetchWithCache.
     */
    public function testFetchWithCache(): void
    {
        // First call should query the database
        $this->mockExecutor->expects($this->once())
            ->method('query')
            ->willReturnSelf();

        $this->mockExecutor->expects($this->once())
            ->method('fetchAll')
            ->willReturn([['id' => 1, 'name' => 'John']]);

        // Mock cache implementation
        $cache = $this->createMock(\Psr\SimpleCache\CacheInterface::class);
        $cache->method('has')->willReturn(false);
        $cache->expects($this->once())->method('set');

        $query = $this->query->withCache($cache, 60);
        $result1 = $query->from('users')->getAllAssoc();

        $this->assertEquals([['id' => 1, 'name' => 'John']], $result1);
    }
}