<?php
/**
 * Aggregate stats repository base class
 */

namespace Javaabu\Stats\Repositories\TimeSeries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Javaabu\Stats\StatsRepository;

abstract class AggregateStatsRepository extends StatsRepository
{
    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $aggregate_sql;

    /**
     * @var string
     */
    protected $date_field = 'created_at';

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
    public function getDateField()
    {
        return $this->getTable().'.'.$this->date_field;
    }

    /**
     * Get the aggregate sql expression for the repository
     *
     * @return string
     */
    public function getAggregateSql()
    {
        return $this->aggregate_sql;
    }

    /**
     * Get the filtered query
     *
     * @param Builder $query
     * @return Builder
     */
    protected function applyDateFilters(Builder $query): Builder
    {
        return $query->whereBetween($this->getDateField(), [$this->getDateFrom(), $this->getDateTo()])
                     ->orderBy($this->getDateField(), 'ASC');
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
    public function day()
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
    public function week()
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
    public function month()
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
    public function year()
    {
        return $this->filteredQuery()
            ->select(DB::raw($this->getAggregateSql().", YEAR(".$this->getDateField().") as year"))
            ->groupBy('year');
    }

    /**
     * Get the total
     *
     * @return int
     */
    public function total()
    {
        return $this->filteredQuery()
                    ->select(DB::raw($this->getAggregateSql()))
                    ->value($this->getAggregateFieldName());
    }
}
