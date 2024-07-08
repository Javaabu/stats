<?php
namespace Javaabu\Stats;


use Javaabu\Models\User;
use Illuminate\Database\Eloquent\Builder;

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
