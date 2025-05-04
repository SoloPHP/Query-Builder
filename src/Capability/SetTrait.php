<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

trait SetTrait
{
    private array $data = [];
    private array $setBindings = [];

    public function set(string|array $column, mixed $value = null): static
    {
        if (is_array($column)) {
            foreach ($column as $key => $val) {
                $this->setColumnValue($key, $val);
            }
        } else {
            $this->setColumnValue($column, $value);
        }

        return $this;
    }

    private function setColumnValue(string $column, mixed $value): void
    {
        $this->data[$column] = $value;

        if (is_string($value) && str_starts_with($value, '{') && str_ends_with($value, '}')) {
            return;
        }

        $this->setBindings[] = $value;
    }

    protected function getSetBindings(): array
    {
        return array_merge($this->setBindings, $this->getBindings());
    }
}