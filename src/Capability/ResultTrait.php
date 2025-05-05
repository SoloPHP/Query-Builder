<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

trait ResultTrait
{
    use CapabilityBase;

    abstract public function build(): array;
    abstract public function buildCount(?string $column = null, bool $distinct = false): array;
    abstract protected function validateExecutor(): void;

    public function getAssoc(): ?array
    {
        return $this->fetchWithCache('assoc', function ($sql, $bindings) {
            $this->executor->query($sql, $bindings);
            return $this->executor->fetch('assoc') ?: null;
        });
    }

    public function getAllAssoc(): array
    {
        return $this->fetchWithCache('all_assoc', function ($sql, $bindings) {
            $this->executor->query($sql, $bindings);
            return $this->executor->fetchAll('assoc');
        });
    }

    public function getObj(string $className = 'stdClass'): ?object
    {
        return $this->fetchWithCache('obj', function ($sql, $bindings) use ($className) {
            $this->executor->query($sql, $bindings);
            return $this->executor->fetch('object', $className) ?: null;
        });
    }

    public function getAllObj(string $className = 'stdClass'): array
    {
        return $this->fetchWithCache('all_obj', function ($sql, $bindings) use ($className) {
            $this->executor->query($sql, $bindings);
            return $this->executor->fetchAll('object', $className);
        });
    }

    public function getValue(): mixed
    {
        return $this->fetchWithCache('value', function ($sql, $bindings) {
            $this->executor->query($sql, $bindings);
            return $this->executor->fetchColumn(0);
        });
    }

    public function getColumn(string $column, ?string $keyColumn = null): array
    {
        $cacheKey = 'column_' . $column . ($keyColumn ? '_by_' . $keyColumn : '');
        return $this->fetchWithCache($cacheKey, function ($sql, $bindings) use ($column, $keyColumn) {
            $this->executor->query($sql, $bindings);
            $results = $this->executor->fetchAll('assoc');

            if (empty($results)) {
                return [];
            }

            if ($keyColumn === null) {
                return array_column($results, $column);
            } else {
                return array_column($results, $column, $keyColumn);
            }
        });
    }

    public function exists(): bool
    {
        return $this->fetchWithCache('exists', function() {
            [$countSql, $countBindings] = $this->buildCount();
            $this->executor->query($countSql, $countBindings);
            return (int)$this->executor->fetchColumn() > 0;
        });
    }

    public function count(?string $column = null, bool $distinct = false): int
    {
        $cacheKey = 'count' . ($column !== null ? '_' . $column : '') . ($distinct ? '_distinct' : '');

        return $this->fetchWithCache($cacheKey, function() use ($column, $distinct) {
            [$countSql, $countBindings] = $this->buildCount($column, $distinct);
            $this->executor->query($countSql, $countBindings);
            return (int)$this->executor->fetchColumn();
        });
    }

    private function fetchWithCache(string $prefix, callable $fetchCallback)
    {
        [$sql, $bindings] = $this->build();
        $key = null;

        if ($this->cacheManager) {
            $key = $this->cacheManager->makeKey($prefix, $sql, $bindings);
            if ($this->cacheManager->has($key)) {
                return $this->cacheManager->get($key);
            }
        }

        $this->validateExecutor();

        $result = $fetchCallback($sql, $bindings);

        if ($this->cacheManager && $key !== null) {
            $this->cacheManager->set($key, $result);
        }

        return $result;
    }
}