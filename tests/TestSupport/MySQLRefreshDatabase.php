<?php

namespace Javaabu\Stats\Tests\TestSupport;

use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

trait MySQLRefreshDatabase
{
    protected bool $use_mysql = true;

    use FastRefreshDatabase;

    public function setUp(): void
    {
        RefreshDatabaseState::$migrated = false;

        $_ENV['DB_CONNECTION'] = 'mysql';
        $_ENV['DB_DATABASE'] = 'stats_test';

        parent::setUp();
    }
}
