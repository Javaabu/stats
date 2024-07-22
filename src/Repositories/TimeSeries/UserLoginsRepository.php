<?php

namespace Javaabu\Stats\Repositories\TimeSeries;

use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Filters\StatsFilter;

class UserLoginsRepository extends ActivityLogStatsRepository
{
    /**
     * @var string
     */
    protected string $aggregate_field = 'logins';

    /**
     * Get all the allowed filters
     */
    public function allowedFilters(): array
    {
        return [
            StatsFilter::exact('user', 'causer_id'),
        ];
    }

    /**
     * Get the base query
     */
    public function query(): Builder
    {
        return $this->baseQuery()->whereCauserType('user');
    }

    public function eventDescription(): string
    {
        return 'login';
    }
}
