<?php
/**
 * Count Stats Repository base class
 */

namespace Javaabu\Stats\Repositories\TimeSeries;

abstract class CountStatsRepository extends AggregateStatsRepository
{
    /**
     * Get the aggregate field name
     */
    public function getAggregateFieldName(): string
    {
        return 'count';
    }

    /**
     * Get the aggregate sql expression for the repository
     */
    public function getAggregateSql(): string
    {
        return 'count(*) as '.$this->getAggregateFieldName();
    }
}
