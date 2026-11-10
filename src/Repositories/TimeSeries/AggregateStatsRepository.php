<?php

namespace Javaabu\Stats\Repositories\TimeSeries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Javaabu\Stats\Enums\TimeSeriesModes;

/**
 * A time series stat that aggregates one table with SQL (count, sum, …),
 * grouped by hour, day, week, month or year
 */
abstract class AggregateStatsRepository extends AbstractTimeSeriesStatsRepository
{
    /**
     * The main table of the stat
     */
    abstract public function getTable(): string;

    /**
     * The aggregate SQL, aliased as the aggregate field name: `count(*) as count`
     */
    abstract public function getAggregateSql(): string;

    /**
     * The SQL that groups the date column into steps of the mode, for the
     * database the stat's query runs on
     */
    public function getDateSqlForTimeMode(TimeSeriesModes $mode): string
    {
        $driver = $this->query()->getConnection()->getDriverName();

        return $mode->getSql($this->getDateField(), $driver);
    }

    /**
     * The date column, without the table
     */
    public function getDateFieldName(): string
    {
        return 'created_at';
    }

    /**
     * The date column, qualified with the table
     */
    public function getDateField(): string
    {
        return $this->getTable() . '.' . $this->getDateFieldName();
    }

    /**
     * The filtered query grouped into steps of the mode, with the step's key
     * in a column named after the mode
     */
    public function groupedQuery(TimeSeriesModes $mode): Builder
    {
        $this->setCurrentMode($mode);

        $alias = $mode->value;

        return $this->filteredQuery()
            ->select(DB::raw($this->getAggregateSql() . ', ' . $this->getDateSqlForTimeMode($mode) . ' as ' . $alias))
            ->groupBy($alias)
            ->orderBy($alias);
    }

    public function hour(): Builder
    {
        return $this->groupedQuery(TimeSeriesModes::HOUR);
    }

    public function day(): Builder
    {
        return $this->groupedQuery(TimeSeriesModes::DAY);
    }

    public function week(): Builder
    {
        return $this->groupedQuery(TimeSeriesModes::WEEK);
    }

    public function month(): Builder
    {
        return $this->groupedQuery(TimeSeriesModes::MONTH);
    }

    public function year(): Builder
    {
        return $this->groupedQuery(TimeSeriesModes::YEAR);
    }

    public function total(): float|int
    {
        $this->setCurrentMode(null);

        $total = $this->filteredQuery()
            ->select(DB::raw($this->getAggregateSql()))
            ->value($this->getAggregateFieldName());

        return is_null($total) ? 0 : $total + 0;
    }
}
