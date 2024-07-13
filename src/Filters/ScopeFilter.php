<?php

namespace Javaabu\Stats\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Javaabu\Stats\Contracts\InteractsWithFilters;

class ScopeFilter extends AbstractFilter
{
    public function __construct(string $name, string $internal_name = '')
    {
        if (! $internal_name) {
            $internal_name = Str::of($name)
                                ->snake(' ')
                                ->camel()
                                ->toString();
        }

        parent::__construct($name, $internal_name);
    }

    public function apply(Builder $query, $value, InteractsWithFilters $stat): Builder
    {
        $method = $this->getInternalName();

        return $query->{$method}($value);
    }
}
