<?php
namespace Javaabu\Stats\Repositories\TimeSeries;


use Illuminate\Database\Eloquent\Builder;
use Javaabu\Helpers\Activitylog\Activity;
use Javaabu\Models\User;

class AdminLoginsRepository extends UserLoginsRepository
{
    /**
     * @var array
     */
    protected $allowed_filters = [
        'user',
    ];

    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @return Builder
     */
    protected function applyFilters(Builder $query): Builder
    {
        if ($user = $this->getFilter('user')) {
            $query->where($this->getTable().'.causer_id', $user);
        }

        return $query;
    }
}
