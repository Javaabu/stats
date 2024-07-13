<?php

namespace Javaabu\Stats\Contracts;

use Carbon\Carbon;

interface DateRange
{
    /**
     * Get the date from
     */
    public function getDateFrom(): Carbon;

    /**
     * Get the date to
     */
    public function getDateTo(): Carbon;

    /**
     * Get the name for the type of date range
     */
    public function getName(): string;
}
