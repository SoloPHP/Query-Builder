<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Clause;

use Solo\QueryBuilder\Contracts\ClauseInterface;
use Solo\QueryBuilder\Contracts\GrammarInterface;

final readonly class OrderByClause implements ClauseInterface
{
    public function __construct(
        private array             $orderings,
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

            if (!str_starts_with($column, '{')) {
                $column = $this->grammar->wrapIdentifier($column);
            } else {
                $column = substr($column, 1, -1);
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