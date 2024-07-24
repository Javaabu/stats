<?php
/**
 * Sum Stats Repository base class
 */

namespace Javaabu\Stats\Repositories\TimeSeries;

abstract class SumStatsRepository extends AggregateStatsRepository
{
    /**
     * Get the aggregate field name
     */
    public function getAggregateFieldName(): string
    {
        return 'total';
    }

    /**
     * Get the main table for the repository
     *
     * @return string
     */
    public function getAggregateSql(): string
    {
        return 'sum('.$this->getFieldToSum().') as '.$this->getAggregateFieldName();
    }

    /**
     * Get the field to sum for the repository
     */
    public abstract function getFieldToSum(): string;
}
