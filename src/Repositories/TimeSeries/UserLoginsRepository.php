<?php

namespace Javaabu\Stats\Repositories\TimeSeries;

class UserLoginsRepository extends LoginsRepository
{
    public function userModelClass(): string
    {
        return \App\Models\User::class;
    }

    public function getName(): string
    {
        return __('Admin User Logins');
    }
}
