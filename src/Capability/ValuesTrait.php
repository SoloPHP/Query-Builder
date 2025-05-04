<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

use Solo\QueryBuilder\Exception\QueryBuilderException;

trait ValuesTrait
{
    private array $columns = [];
    private array $rows = [];
    private array $valueBindings = [];

    public function values(array $data): static
    {
        if (isset($data[0]) && is_array($data[0])) {
            foreach ($data as $row) {
                $this->addRow($row);
            }
        } else {
            $this->addRow($data);
        }

        return $this;
    }

    private function addRow(array $row): void
    {
        if (empty($this->columns)) {
            $this->columns = array_keys($row);
        }

        if (array_keys($row) !== $this->columns) {
            throw new QueryBuilderException(
                'Columns of all rows must match: expected '
                . implode(',', $this->columns)
            );
        }

        $this->rows[] = $row;
        $this->valueBindings = array_merge(
            $this->valueBindings,
            array_values($row)
        );
    }

    protected function getAllBindings(): array
    {
        return array_merge($this->getBindings(), $this->valueBindings);
    }
}