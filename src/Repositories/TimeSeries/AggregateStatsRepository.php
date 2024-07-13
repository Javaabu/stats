<?php
/**
 * Aggregate stats repository base class
 */

namespace Javaabu\Stats\Repositories\TimeSeries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

abstract class AggregateStatsRepository extends AbstractTimeSeriesStatsRepository
{
    /**
     * @var string
     */
    protected string $table;

    /**
     * @var string
     */
    protected string $aggregate_sql;

    /**
     * @var string
     */
    protected string $date_field = 'created_at';

    /**
     * Get the main table for the repository
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get the date field for the repository
     *
     * @return string
     */
    public function getDateField(): string
    {
        return $this->getTable().'.'.$this->date_field;
    }

    /**
     * Get the aggregate sql expression for the repository
     *
     * @return string
     */
    public function getAggregateSql(): string
    {
        return $this->aggregate_sql;
    }

    /**
     * Get the hourly query
     *
     * @return Builder
     */
    public function hour()
    {
        return $this->filteredQuery()
            ->select(DB::raw($this->getAggregateSql().", DATE_FORMAT(".$this->getDateField().", '%Y-%m-%d %H:00') as hour"))
            ->groupBy('hour');
    }

    /**
     * Get the day query
     *
     * @return Builder
     */
    public function day(): Builder
    {
        return $this->filteredQuery()
            ->select(DB::raw($this->getAggregateSql().", DATE(".$this->getDateField().") as day"))
            ->groupBy('day');
    }

    /**
     * Get the week query
     *
     * @return Builder
     */
    public function week(): Builder
    {
        return $this->filteredQuery()
            //->select(DB::raw($this->getAggregateSql().", DATE_FORMAT(DATE_ADD(".$this->getDateField().", INTERVAL(1-DAYOFWEEK(".$this->getDateField().")) DAY), '%X-%m-%d, %V') as week"))
            ->select(DB::raw($this->getAggregateSql().", DATE_FORMAT(".$this->getDateField().", '%X, %V') as week"))
            ->groupBy('week');
    }

    /**
     * Get the month query
     *
     * @return Builder
     */
    public function month(): Builder
    {
        return $this->filteredQuery()
            ->select(DB::raw($this->getAggregateSql().", DATE_FORMAT(".$this->getDateField().", '%Y, %m') as month"))
            ->groupBy('month');
    }

    /**
     * Get the year query
     *
     * @return Builder
     */
    public function year(): Builder
    {
        return $this->filteredQuery()
            ->select(DB::raw($this->getAggregateSql().", YEAR(".$this->getDateField().") as year"))
            ->groupBy('year');
    }

    /**
     * Get the total
     */
    public function total(): float|int
    {
        return $this->filteredQuery()
                    ->select(DB::raw($this->getAggregateSql()))
                    ->value($this->getAggregateFieldName());
    }
}
