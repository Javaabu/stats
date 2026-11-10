<?php

namespace Javaabu\Stats\Repositories\TimeSeries;

use Illuminate\Database\Eloquent\Model;

/**
 * Sign-ups of the users of `config('stats.user_model')`, or of the model of
 * the default auth guard's provider
 */
class UserSignupsRepository extends SignupsRepository
{
    /**
     * @return class-string<Model>|null
     */
    public static function defaultUserModel(): ?string
    {
        $model = config('stats.user_model');

        if ($model) {
            return $model;
        }

        $guard = config('auth.defaults.guard');
        $provider = config("auth.guards.{$guard}.provider");

        return config("auth.providers.{$provider}.model");
    }

    public function userModelClass(): string
    {
        return static::defaultUserModel();
    }

    public function getName(): string
    {
        return __('User Signups');
    }
}
