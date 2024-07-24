<?php
namespace Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries;

use Javaabu\Stats\Repositories\TimeSeries\LoginsRepository;
use Javaabu\Stats\Tests\TestSupport\Models\User;

class TestUserLoginsRepository extends LoginsRepository
{

    public function userModelClass(): string
    {
        return User::class;
    }
}
