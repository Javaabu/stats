<?php

namespace Javaabu\Stats\Contracts;

use Carbon\Carbon;

interface DateRange
{
    /**
     * Get the date from
     */
    public function getDateFrom(?Carbon $min_date = null): Carbon;

    /**
     * Get the date to
     */
    public function getDateTo(?Carbon $max_date = null): Carbon;

    /**
     * Get the name for the type of date range
     */
    public function getName(): string;
}
