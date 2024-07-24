<?php

namespace Javaabu\Stats\Repositories\TimeSeries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Javaabu\Stats\Filters\StatsFilter;

abstract class LoginsRepository extends ActivityLogStatsRepository
{
    /**
     * Get the aggregate field name
     */
    public function getAggregateFieldName(): string
    {
        return 'logins';
    }

    /**
     * Get all the allowed filters
     */
    public function allowedFilters(): array
    {
        return [
            StatsFilter::exact($this->userMorphClass(), 'causer_id'),
        ];
    }

    /**
     * Get the base query
     */
    public function query(): Builder
    {
        return $this->baseQuery()
                    ->whereCauserType($this->userMorphClass());
    }

    public function eventDescription(): string
    {
        return 'login';
    }

    /**
     * Get the use morph class
     */
    public function userMorphClass(): string
    {
        $user_class = $this->userModelClass();

        return (new $user_class)->getMorphClass();
    }


    /**
     * Get the model class
     * @return class-string<Model>
     */
    public abstract function userModelClass(): string;
}
