<?php
/**
 * Revenue Stats Repository base class
 */

namespace Javaabu\Stats\Repositories\TimeSeries;

abstract class TotalAmountStatsRepository extends AggregateStatsRepository
{
    /**
     * @var string
     */
    protected string $aggregate_field = 'total_mvr';

    /**
     * @var string
     */
    protected string $sum_field = 'amount';

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
     *
     * @return string
     */
    public function getFieldToSum(): string
    {
        return $this->sum_field;
    }
}
