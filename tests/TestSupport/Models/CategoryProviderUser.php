<?php

namespace Javaabu\Stats\Tests\TestSupport\Models;

use Javaabu\Stats\Concerns\IsCategoricalStatsProvider;
use Javaabu\Stats\Contracts\CategoryProvider;

class CategoryProviderUser extends User implements CategoryProvider
{
    use IsCategoricalStatsProvider;

    protected $table = 'users';

    public function getCategoricalStatsCategoryTitle(): string
    {
        return 'Account';
    }

    public function getCategoricalStatsCategoryIdTitle(): string
    {
        return 'Account Number';
    }

    public function scopeCategoricalStatsSearch($query, $search)
    {
        return $query->where('email', 'like', '%'.$search.'%');
    }
}
