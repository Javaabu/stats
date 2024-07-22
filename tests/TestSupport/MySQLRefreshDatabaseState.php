<?php

namespace Javaabu\Stats\Tests\TestSupport;

use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class MySQLRefreshDatabaseState
{
    public static $migrated = false;
    public static $driver_switched = false;
}
