<?php
/**
 * Count Stats Repository base class
 */

namespace Javaabu\Stats\Repositories\TimeSeries;

abstract class CountStatsRepository extends AggregateStatsRepository
{
    /**
     * @var string
     */
    protected $aggregate_field = 'count';

    /**
     * Get the aggregate sql expression for the repository
     *
     * @return string
     */
    public function getAggregateSql()
    {
        return 'count(*) as '.$this->getAggregateFieldName();
    }
}
