<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Utility;

final class Raw
{
    public static function is(string $value): bool
    {
        return str_starts_with($value, '{') && str_ends_with($value, '}');
    }

    public static function get(string $value): string
    {
        return self::is($value) ? substr($value, 1, -1) : $value;
    }
}