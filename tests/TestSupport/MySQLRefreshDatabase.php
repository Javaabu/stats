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
        $this->setupMySql();

        parent::setUp();
    }

    protected function setupMySql(): void
    {
        if (! MySQLRefreshDatabaseState::$migrated) {
            RefreshDatabaseState::$migrated = false;
        }

        MySQLRefreshDatabaseState::$migrated = true;
        MySQLRefreshDatabaseState::$driver_switched = true;

        $_ENV['DB_CONNECTION'] = 'mysql';
        $_ENV['DB_DATABASE'] = 'stats_test';
    }
}
