<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

use Solo\QueryBuilder\Clause\SetClause;
use Solo\QueryBuilder\Utility\Raw;
use Solo\QueryBuilder\Enum\ClausePriority;

trait SetTrait
{
    use CapabilityBase;

    private array $data = [];

    public function set(string|array $column, mixed $value = null): static
    {
        if (is_array($column)) {
            foreach ($column as $key => $val) {
                $this->data[$key] = $val;
            }
        } else {
            $this->data[$column] = $value;
        }

        $this->clearExistingSetClauses();
        $this->addSetClause();

        return $this;
    }

    private function clearExistingSetClauses(): void
    {
        $this->filterClauses(SetClause::class);
    }

    private function addSetClause(): void
    {
        $assignments = [];
        $bindings = [];

        foreach ($this->data as $col => $val) {
            if (is_string($val) && Raw::is($val)) {
                $rawValue = Raw::get($val);
                $assignments[] = $this->getGrammar()->wrapIdentifier($col) . " = " . $rawValue;
            } else {
                $assignments[] = $this->getGrammar()->wrapIdentifier($col) . " = ?";
                $bindings[] = $val;
            }
        }

        if (!empty($assignments)) {
            $this->addClause(
                new SetClause($assignments, $bindings),
                ClausePriority::SET
            );
        }
    }
}
