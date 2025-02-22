<?php declare(strict_types=1);

namespace Solo\QueryBuilder\Traits;

trait TableParserTrait
{
    protected function parseTable(string $table): array
    {
        $parts = explode('|', $table);
        return [
            $parts[0],
            $parts[1] ?? substr($parts[0], 0, 1)
        ];
    }
}