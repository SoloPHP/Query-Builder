<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Factory;

use Solo\QueryBuilder\Contracts\{GrammarFactoryInterface, GrammarInterface};
use Solo\QueryBuilder\Grammar\{MySqlGrammar, PostgresGrammar, SQLiteGrammar};
use Solo\QueryBuilder\Exception\QueryBuilderException;

final class GrammarFactory implements GrammarFactoryInterface
{
    private const DATABASE_TYPES = [
        'mysql' => 'mysql',
        'mariadb' => 'mysql',
        'postgresql' => 'postgresql',
        'postgres' => 'postgresql',
        'pgsql' => 'postgresql',
        'sqlite' => 'sqlite',
        'sqlite3' => 'sqlite',
    ];

    public function create(string $type): GrammarInterface
    {
        $normalizedType = strtolower(trim($type));

        if (!isset(self::DATABASE_TYPES[$normalizedType])) {
            throw new QueryBuilderException("Unsupported database type: $type");
        }

        $databaseType = self::DATABASE_TYPES[$normalizedType];

        return match($databaseType) {
            'mysql' => new MySqlGrammar(),
            'postgresql' => new PostgresGrammar(),
            'sqlite' => new SQLiteGrammar(),
            default => throw new QueryBuilderException("Unsupported database type: $type")
        };
    }
}