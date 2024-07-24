<?php
namespace Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries;

use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Filters\StatsFilter;
use Javaabu\Stats\Repositories\TimeSeries\CountStatsRepository;

class UserLogoutsWithPermission extends CountStatsRepository
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
