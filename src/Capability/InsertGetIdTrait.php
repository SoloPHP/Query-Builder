<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

trait InsertGetIdTrait
{
    use ExecutableTrait;

    public function insertGetId(): int|string|null
    {
        if (!$this->executor) {
            throw new \RuntimeException('No executor available to execute the query');
        }

        [$sql, $bindings] = $this->build();
        $this->executor->query($sql, $bindings);
        $id = $this->executor->lastInsertId();

        if ($id === false) {
            return null;
        }

        return is_numeric($id) ? (int)$id : $id;
    }
}