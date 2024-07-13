<?php

namespace Javaabu\Stats\Filters;

class StatsFilter
{
    public static function scope(string $name, string $internal_name = ''): ScopeFilter
    {
        return new ScopeFilter($name, $internal_name);
    }

    public static function exact(string $name, string $internal_name = ''): ExactFilter
    {
        return new ExactFilter($name, $internal_name);
    }

    public static function closure(string $name, \Closure $callback): ClosureFilter
    {
        return new ClosureFilter($name, $callback);
    }
}
