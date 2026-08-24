<?php

namespace Javaabu\Stats\Tests\TestSupport\Models;

class CategoricalSearchUser extends User
{
    protected $table = 'users';

    public function scopeCategoricalStatsSearch($query, $search)
    {
        return $query->where('email', 'like', '%'.$search.'%');
    }
}
