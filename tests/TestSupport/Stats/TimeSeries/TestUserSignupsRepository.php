<?php
namespace Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries;

use Javaabu\Stats\Repositories\TimeSeries\SignupsRepository;
use Javaabu\Stats\Tests\TestSupport\Models\User;

class TestUserSignupsRepository extends SignupsRepository
{

    public function userModelClass(): string
    {
        return User::class;
    }
}
