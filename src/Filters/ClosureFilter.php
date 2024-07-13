<?php

namespace Javaabu\Stats\Filters;

use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Contracts\InteractsWithFilters;

class ClosureFilter extends AbstractFilter
{
    protected \Closure $callback;

    public function __construct(string $name, \Closure $callback)
    {
        $this->callback = $callback;

        parent::__construct($name);
    }

    public function apply(Builder $query, $value, InteractsWithFilters $stat): Builder
    {
        return app()->call($this->callback, compact('query', 'value', 'stat'));
    }
}
