<?php

namespace Javaabu\Stats\Contracts;

use Carbon\CarbonInterface;

interface DateRange
{
    /**
     * Get the date from
     */
    public function getDateFrom(): CarbonInterface;

    /**
     * Get the date to
     */
    public function getDateTo(): CarbonInterface;

    /**
     * Get the name for the type of date range
     */
    public function getName(): string;

    /**
     * Get the previous date range
     */
    public function getPreviousDateRange(): DateRange;
}
