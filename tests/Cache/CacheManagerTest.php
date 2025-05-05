<?php declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Cache;

use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Solo\QueryBuilder\Cache\CacheManager;

class CacheManagerTest extends TestCase
{
    public function testMakeKeyProducesDeterministicHash(): void
    {
        $cache = new class implements CacheInterface {
            private array $data = [];
            public function get($key, $default = null) { return $this->data[$key] ?? $default; }
            public function set($key, $value, $ttl = null) { $this->data[$key] = $value; return true; }
            public function delete($key) { unset($this->data[$key]); return true; }
            public function clear() { $this->data = []; return true; }
            public function getMultiple($keys, $default = null) { throw new \BadMethodCallException; }
            public function setMultiple($values, $ttl = null) { throw new \BadMethodCallException; }
            public function deleteMultiple($keys) { throw new \BadMethodCallException; }
            public function has($key) { return array_key_exists($key, $this->data); }
        };
        $manager = new CacheManager($cache, 60);

        $sql      = 'SELECT * FROM tbl WHERE id = ?';
        $bindings = [42];
        $key1 = $manager->makeKey('test', $sql, $bindings);
        $key2 = $manager->makeKey('test', $sql, $bindings);

        $this->assertSame($key1, $key2);
        $this->assertStringStartsWith('qb:test:', $key1);
    }

    public function testSetGetHasDeleteBehavior(): void
    {
        $cache = new class implements CacheInterface {
            private array $data = [];
            public function get($key, $default = null) { return $this->data[$key] ?? $default; }
            public function set($key, $value, $ttl = null) { $this->data[$key] = $value; return true; }
            public function delete($key) { unset($this->data[$key]); return true; }
            public function clear() { $this->data = []; return true; }
            public function getMultiple($keys, $default = null) { throw new \BadMethodCallException; }
            public function setMultiple($values, $ttl = null) { throw new \BadMethodCallException; }
            public function deleteMultiple($keys) { throw new \BadMethodCallException; }
            public function has($key) { return array_key_exists($key, $this->data); }
        };
        $manager = new CacheManager($cache, 60);

        $key   = 'foo';
        $value = ['a'=>1];

        $this->assertFalse($manager->has($key));
        $manager->set($key, $value);
        $this->assertTrue($manager->has($key));
        $this->assertSame($value, $manager->get($key));
        $manager->delete($key);
        $this->assertFalse($manager->has($key));
    }
}
