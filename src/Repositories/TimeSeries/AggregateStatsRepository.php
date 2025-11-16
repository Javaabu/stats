<?php
/**
 * Aggregate stats repository base class
 */

namespace Javaabu\Stats\Repositories\TimeSeries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\TimeSeriesStats;

abstract class AggregateStatsRepository extends AbstractTimeSeriesStatsRepository
{
    /**
     * Get the main table for the repository
     */
    public abstract function getTable(): string;

    /**
     * Get the aggregate sql expression for the repository
     */
    public abstract function getAggregateSql(): string;

    public function getDateSqlForTimeMode(TimeSeriesModes $mode): string
    {
        return $mode->getSql($this->getDateField());
    }

    /**
     * Get the date field name for the repository
     */
    public function getDateFieldName(): string
    {
        return 'created_at';
    }

    /**
     * Get the date field for the repository
     */
    public function getDateField(): string
    {
        return $this->getTable().'.'.$this->getDateFieldName();
    }

    /**
     * Get the hourly query
     *
     * @return Builder
     */
    public function hour(): Builder
    {
        $this->setCurrentMode(TimeSeriesModes::HOUR);

        return $this->filteredQuery()
            ->select(DB::raw($this->getAggregateSql().", " . $this->getDateSqlForTimeMode(TimeSeriesModes::HOUR) . " as hour"))
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
        $this->setCurrentMode(TimeSeriesModes::DAY);

        return $this->filteredQuery()
            ->select(DB::raw($this->getAggregateSql().", " . $this->getDateSqlForTimeMode(TimeSeriesModes::DAY) . " as day"))
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
        $this->setCurrentMode(TimeSeriesModes::WEEK);

        return $this->filteredQuery()
            //->select(DB::raw($this->getAggregateSql().", DATE_FORMAT(".$this->getDateField().", '%X, %V') as week"))
            ->select(DB::raw($this->getAggregateSql().", " . $this->getDateSqlForTimeMode(TimeSeriesModes::WEEK) . " as week"))
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
        $this->setCurrentMode(TimeSeriesModes::MONTH);

        return $this->filteredQuery()
            ->select(DB::raw($this->getAggregateSql().", " . $this->getDateSqlForTimeMode(TimeSeriesModes::MONTH) . " as month"))
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
        $this->setCurrentMode(TimeSeriesModes::YEAR);

        return $this->filteredQuery()
            ->select(DB::raw($this->getAggregateSql().", " . $this->getDateSqlForTimeMode(TimeSeriesModes::YEAR) . " as year"))
            ->groupBy('year')
            ->orderBy('year', 'ASC');
    }

    /**
     * Get the total
     */
    public function total(): float|int
    {
        $this->setCurrentMode(null);

        $total = $this->filteredQuery()
                    ->select(DB::raw($this->getAggregateSql()))
                    ->value($this->getAggregateFieldName());

        return is_null($total) ? 0 : $total;
    }
}
