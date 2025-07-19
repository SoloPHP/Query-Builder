<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

use Solo\QueryBuilder\Exception\QueryBuilderException;

trait ValuesTrait
{
    protected array $columns = [];
    protected array $rows = [];

    public function values(array $data): static
    {
        if (isset($data[0]) && is_array($data[0])) {
            $this->processMultipleRows($data);
        } else {
            $this->addRow($data);
        }

        return $this;
    }

    private function processMultipleRows(array $rows): void
    {
        foreach ($rows as $row) {
            $this->addRow($row);
        }
    }

    private function addRow(array $row): void
    {
        if (empty($this->columns)) {
            $this->columns = array_keys($row);
        }

        $this->validateRowColumns($row);
        $this->rows[] = $row;
        $this->addBindings(array_values($row));
    }

    private function validateRowColumns(array $row): void
    {
        if (array_keys($row) !== $this->columns) {
            throw new QueryBuilderException(
                'Columns of all rows must match: expected '
                . implode(',', $this->columns)
            );
        }
    }
}
