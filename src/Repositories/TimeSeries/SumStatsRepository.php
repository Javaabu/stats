<?php

/**
 * Sum Stats Repository base class
 */

namespace Javaabu\Stats\Repositories\TimeSeries;

abstract class SumStatsRepository extends AggregateStatsRepository
{
    /**
     * Get the main table for the repository
     */
    public function getAggregateSql(): string
    {
        return 'sum(' . $this->getFieldToSum() . ') as ' . $this->getAggregateFieldName();
    }

    /**
     * Get the field to sum for the repository
     */
    abstract public function getFieldToSum(): string;
}
