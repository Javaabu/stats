<?php
/**
 * Revenue Stats Repository base class
 */

namespace Javaabu\Stats;

abstract class TotalAmountStatsRepository extends AggregateStatsRepository
{
    /**
     * @var string
     */
    protected $aggregate_field = 'total_mvr';

    /**
     * @var string
     */
    protected $sum_field = 'amount';

    /**
     * Get the main table for the repository
     *
     * @return string
     */
    public function getAggregateSql()
    {
        return 'sum('.$this->getFieldToSum().') as '.$this->getAggregateFieldName();
    }

    /**
     * Get the field to sum for the repository
     *
     * @return string
     */
    public function getFieldToSum()
    {
        return $this->sum_field;
    }
}
