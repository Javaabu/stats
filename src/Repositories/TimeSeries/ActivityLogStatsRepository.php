<?php

namespace Javaabu\Stats\Repositories\TimeSeries;

use Illuminate\Database\Eloquent\Builder;

abstract class ActivityLogStatsRepository extends CountStatsRepository
{

    /**
     * Get the main table for the repository
     */
    public function getTable(): string
    {
        return config('activitylog.table_name');
    }

    /**
     * Get the base query
     */
    public function baseQuery(): Builder
    {
        $model = config('activitylog.activity_model');

        return $model::query()
                ->whereDescription($this->eventDescription());
    }

    /**
     * Get the base query
     */
    public function query(): Builder
    {
        return $this->baseQuery();
    }

    /**
     * Get the event to count
     */
    public abstract function eventDescription(): string;
}
