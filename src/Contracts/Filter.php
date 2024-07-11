<?php

namespace Javaabu\Stats\Contracts;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

interface Filter
{
    /**
     * Get the name of the filter
     */
    public function getName(): string;

    /**
     * Get the date to
     */
    public function apply(Builder $query, $value, InteractsWithFilters $stat): Carbon;
}
