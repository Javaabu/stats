<?php

namespace Javaabu\Stats\Filters;

use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Contracts\InteractsWithFilters;

class ExactFilter extends AbstractFilter
{
    public function apply(Builder $query, $value, InteractsWithFilters $stat): Builder
    {
        return $query->where($this->getInternalName(), $value);
    }
}
