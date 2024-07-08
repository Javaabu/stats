<?php
namespace Javaabu\Stats\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasUser
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
            $query->where($this->getTable().'.user_id', $user);
        }

        return $query;
    }
}
