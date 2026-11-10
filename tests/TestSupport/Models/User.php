<?php

namespace Javaabu\Stats\Tests\TestSupport\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Javaabu\Stats\Tests\TestSupport\Factories\UserFactory;

class User extends Authenticatable
{
    use HasFactory;
    use SoftDeletes;

    protected static function newFactory()
    {
        return new UserFactory;
    }

    public function scopeSearch($query, $search)
    {
        return $query->whereLike('name', '%' . $search . '%');
    }

    public function getAdminLinkNameAttribute(): string
    {
        return $this->name;
    }

    public function scopeCategoricalStatsSearch($query, $search)
    {
        return $query->whereLike('name', '%' . $search . '%');
    }

    public function getMorphClass()
    {
        return 'user';
    }
}
