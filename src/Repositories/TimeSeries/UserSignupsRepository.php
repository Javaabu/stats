<?php
namespace Javaabu\Stats\Repositories\TimeSeries;


use Illuminate\Database\Eloquent\Builder;
use Javaabu\Models\User;

class UserSignupsRepository extends CountStatsRepository
{
    /**
     * @var string
     */
    protected $table = 'users';

    /**
     * @var string
     */
    protected $aggregate_field = 'signups';

    /**
     * Get the base query
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query(): Builder
    {
        return User::query();
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query;
    }
}
