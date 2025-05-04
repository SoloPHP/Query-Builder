<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Cache;

use Psr\SimpleCache\CacheInterface;

final class CacheManager
{
    private CacheInterface $cache;
    private ?int $ttl;

    public function __construct(CacheInterface $cache, ?int $ttl = null)
    {
        $this->cache = $cache;
        $this->ttl = $ttl;
    }

    public function makeKey(string $prefix, string $sql, array $bindings): string
    {
        $bindingsKey = '';
        foreach ($bindings as $val) {
            $bindingsKey .= '|' . (is_scalar($val) ? $val : serialize($val));
        }

        return 'qb:' . $prefix . ':' . hash('sha256', $sql . $bindingsKey);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    public function get(string $key)
    {
        return $this->cache->get($key);
    }

    public function set(string $key, $value): void
    {
        $this->cache->set($key, $value, $this->ttl);
    }

    public function delete(string $key): void
    {
        $this->cache->delete($key);
    }
}