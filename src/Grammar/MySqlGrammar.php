<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Grammar;

final class MySqlGrammar extends AbstractGrammar
{
    protected string $tableQuote = '`';
    protected string $columnQuote = '`';
}