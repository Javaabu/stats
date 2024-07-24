<?php

namespace Javaabu\Stats\Repositories\TimeSeries;


class UserSignupsRepository extends SignupsRepository
{

    public function userModelClass(): string
    {
        return \App\Models\User::class;
    }
}
