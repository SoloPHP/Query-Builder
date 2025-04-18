<?php

namespace Solo\QueryBuilder\Builders;

use Solo\Database;
use Solo\QueryBuilder\Components\ConditionBuilder;

final class CountBuilder
{
    private Database $db;
    private string $table;
    private string $alias;
    private array $joins;
    private ConditionBuilder $conditionBuilder;
    private string $groupBy;

    public function __construct(
        Database         $db,
        string           $table,
        string           $alias,
        array            $joins,
        ConditionBuilder $conditionBuilder,
        string           $groupBy
    )
    {
        $this->db = $db;
        $this->table = $table;
        $this->alias = $alias;
        $this->joins = $joins;
        $this->conditionBuilder = $conditionBuilder;
        $this->groupBy = $groupBy;
    }

    public function compile(): string
    {
        $select = "SELECT COUNT(*) AS count";
        $from   = " FROM ?t AS ?c";
        $joins  = $this->joins ? ' ' . implode(' ', $this->joins) : '';

        $where  = $this->conditionBuilder->hasWhereConditions()
            ? ' WHERE ' . $this->conditionBuilder->buildWhere()
            : '';

        $group  = $this->groupBy ? ' ' . $this->groupBy : '';

        $having = $this->conditionBuilder->hasHavingConditions()
            ? ' HAVING ' . $this->conditionBuilder->buildHaving()
            : '';

        $rawSql = "$select$from$joins$where$group$having";

        $params = array_merge(
            [$this->table, $this->alias],
            $this->conditionBuilder->getWhereBindings(),
            $this->conditionBuilder->getHavingBindings()
        );

        return $this->db->prepare($rawSql, ...$params);
    }

    public function getValue(): int
    {
        $stmt = $this->db->query($this->compile());
        $value = $stmt->fetchColumn();
        return (int)($value ?? 0);
    }
}