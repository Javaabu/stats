<?php
/**
 * Aggregate stats repository base class
 */

namespace Javaabu\Stats\Repositories\TimeSeries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Javaabu\Stats\TimeSeriesStats;

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
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get the date field for the repository
     */
    public function getDateField(): string
    {
        return $this->getTable().'.'.$this->date_field;
    }

    /**
     * Get the aggregate sql expression for the repository
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
    public function hour(): Builder
    {
        return $this->filteredQuery()
            ->select(DB::raw($this->getAggregateSql().", DATE_FORMAT(".$this->getDateField().", '%Y-%m-%d %H:00') as hour"))
            ->groupBy('hour')
            ->orderBy('hour', 'ASC');
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
            ->groupBy('day')
            ->orderBy('day', 'ASC');
    }

    /**
     * Get the week query
     *
     * @return Builder
     */
    public function week(): Builder
    {
        $week_mode = TimeSeriesStats::weekMode();

        return $this->filteredQuery()
            //->select(DB::raw($this->getAggregateSql().", DATE_FORMAT(".$this->getDateField().", '%X, %V') as week"))
            ->select(DB::raw($this->getAggregateSql().", YEARWEEK(".$this->getDateField().", $week_mode) as week"))
            ->groupBy('week')
            ->orderBy('week', 'ASC');
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
            ->groupBy('month')
            ->orderBy('month', 'ASC');
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
            ->groupBy('year')
            ->orderBy('year', 'ASC');
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
