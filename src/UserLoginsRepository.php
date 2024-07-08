<?php
namespace Javaabu\Stats;


use Javaabu\Helpers\Activitylog\Activity;
use Javaabu\Models\User;
use Illuminate\Database\Eloquent\Builder;

abstract class UserLoginsRepository extends CountStatsRepository
{

    /**
     * @var string
     */
    protected $table = 'activity_log';

    /**
     * @var string
     */
    protected $aggregate_field = 'logins';

    /**
     * Get the base query
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function baseQuery(): Builder
    {
        return Activity::query()->whereDescription('login');
    }

    /**
     * Get the base query
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query(): Builder
    {
        return $this->baseQuery()->whereCauserType('user');
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @return Builder
     */
    protected function applyFilters(Builder $query): Builder
    {
        return $query;
    }
}
