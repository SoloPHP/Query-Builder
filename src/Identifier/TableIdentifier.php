<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Identifier;

final readonly class TableIdentifier
{
    public string $table;
    public ?string $alias;
    public bool $isSubquery;

    public function __construct(
        string $table,
        ?string $alias = null,
        bool $isSubquery = false
    ) {
        $this->isSubquery = $isSubquery;

        if ($alias === null && !$isSubquery) {
            // First try to match 'table AS alias' pattern
            if (preg_match('/\s+AS\s+/i', $table)) {
                $parts = preg_split('/\s+AS\s+/i', $table, 2);
                if (count($parts) === 2) {
                    $this->table = trim($parts[0]);
                    $this->alias = trim($parts[1]);
                    return;
                }
            }
            // Then try to match 'table alias' pattern (without AS keyword)
            elseif (preg_match('/^([^\s]+)\s+([^\s]+)$/', trim($table), $matches)) {
                $this->table = $matches[1];
                $this->alias = $matches[2];
                return;
            }
        }

        $this->table = $table;
        $this->alias = $alias;
    }

    public function __toString(): string
    {
        if ($this->isSubquery) {
            return $this->alias
                ? sprintf('(%s) AS %s', $this->table, $this->alias)
                : sprintf('(%s)', $this->table);
        }

        return $this->alias
            ? sprintf('%s AS %s', $this->table, $this->alias)
            : $this->table;
    }
}
