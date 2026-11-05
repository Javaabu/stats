<?php

namespace Javaabu\Stats\Repositories\TimeSeries;

/**
 * Logins of the users of `config('stats.user_model')`, or of the model of the
 * default auth guard's provider, from spatie/laravel-activitylog's `login` events
 */
class UserLoginsRepository extends LoginsRepository
{
    public function userModelClass(): string
    {
        return UserSignupsRepository::defaultUserModel();
    }

    public function getName(): string
    {
        return __('User Logins');
    }
}
