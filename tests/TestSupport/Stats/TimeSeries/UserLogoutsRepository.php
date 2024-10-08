<?php
namespace Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Filters\StatsFilter;
use Javaabu\Stats\Repositories\TimeSeries\CountStatsRepository;

class UserLogoutsRepository extends CountStatsRepository
{
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
    public function baseQuery(): Builder
    {
        $model = config('activitylog.activity_model');

        return $model::query()->whereDescription('logout');
    }

    /**
     * Get the base query
     */
    public function query(): Builder
    {
        return $this->baseQuery()->whereCauserType('user');
    }

    /**
     * Check whether the given user can view the stat
     */
    public function canView(?Authorizable $user = null): bool
    {
        return true;
    }

    public function getTable(): string
    {
        return 'activity_log';
    }

    /**
     * Get the aggregate field name
     */
    public function getAggregateFieldName(): string
    {
        return 'logouts';
    }
}
