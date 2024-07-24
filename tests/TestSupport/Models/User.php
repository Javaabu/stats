<?php

namespace Javaabu\Stats\Tests\TestSupport\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Javaabu\Stats\Tests\TestSupport\Factories\UserFactory;
use Spatie\Activitylog\Traits\CausesActivity;

class User extends Authenticatable
{
    use HasFactory;
    use CausesActivity;
    use SoftDeletes;

    protected static function newFactory()
    {
        return new UserFactory();
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', '%' . $search . '%');
    }

    public function getMorphClass()
    {
        return 'user';
    }
}
