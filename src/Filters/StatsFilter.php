<?php

namespace Javaabu\Stats\Filters;

use Closure;
use Javaabu\Filters\Filter;

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

    public static function closure(string $name, Closure $callback): ClosureFilter
    {
        return new ClosureFilter($name, $callback);
    }

    /**
     * A javaabu/filters filter, with fields in the stats form
     */
    public static function from(Filter $filter): ModelFilter
    {
        return new ModelFilter($filter);
    }
}
