<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Clause;

use Solo\QueryBuilder\Contracts\ClauseInterface;
use Solo\QueryBuilder\Contracts\GrammarInterface;
use Solo\QueryBuilder\Enum\ClausePriority;
use Solo\QueryBuilder\Utility\Raw;

final readonly class OrderByClause implements ClauseInterface
{
    public const TYPE = ClausePriority::ORDER_BY;

    public function __construct(
        private array $orderings,
        private ?GrammarInterface $grammar = null
    ) {
    }

    public function compileClause(): string
    {
        if (empty($this->orderings)) {
            return '';
        }

        $processedOrderings = $this->processOrderings();
        return 'ORDER BY ' . implode(', ', $processedOrderings);
    }

    private function processOrderings(): array
    {
        if (!$this->grammar) {
            return array_map(
                fn($ordering) => $ordering['column'] . ' ' . $ordering['direction'],
                $this->orderings
            );
        }

        $result = [];
        foreach ($this->orderings as $ordering) {
            $column = $ordering['column'];
            $direction = $ordering['direction'];

            if (!Raw::is($column)) {
                $column = $this->grammar->wrapIdentifier($column);
            } else {
                $column = Raw::get($column);
            }

            $result[] = $column . ' ' . $direction;
        }

        return $result;
    }

    public function bindings(): array
    {
        return [];
    }
}
