<?php

namespace Javaabu\Stats\Tests;

use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Javaabu\Forms\FormsServiceProvider;
use Javaabu\GeneratorHelpers\Testing\InteractsWithTestFiles;
use Javaabu\GeneratorHelpers\Testing\InteractsWithTestStubs;
use Javaabu\Helpers\HelpersServiceProvider;
use Javaabu\Settings\SettingsServiceProvider;
use Javaabu\Settings\Testing\FakesSettings;
use Javaabu\Stats\Tests\TestSupport\MySQLRefreshDatabaseState;
use Maatwebsite\Excel\ExcelServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Javaabu\Stats\StatsServiceProvider;
use Javaabu\Stats\Tests\TestSupport\Providers\TestServiceProvider;
use Spatie\Activitylog\ActivitylogServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use FakesSettings;
    use InteractsWithTestStubs;
    use InteractsWithTestFiles;

    public function setUp(): void
    {
        if (isset($_ENV['DB_CONNECTION']) || isset($_ENV['DB_DATABASE'])) {
            if (! $this->shouldUseMysql()) {

                if (MySQLRefreshDatabaseState::$driver_switched) {
                    RefreshDatabaseState::$migrated = false;
                }

                MySQLRefreshDatabaseState::$driver_switched = false;

                $_ENV['DB_CONNECTION'] = 'sqlite';
                $_ENV['DB_DATABASE'] = ':memory:';
            }
        }

        parent::setUp();

        $this->loadTestStubsFrom( __DIR__ . '/TestSupport/stubs');

        $this->app['config']->set('app.key', 'base64:yWa/ByhLC/GUvfToOuaPD7zDwB64qkc/QkaQOrT5IpE=');

        $this->app['config']->set('session.serialization', 'php');

        if (empty(glob($this->app->databasePath('migrations/*_create_activity_log_table.php')))) {
            Artisan::call('vendor:publish', [
                '--provider' => 'Spatie\\Activitylog\\ActivitylogServiceProvider',
                '--tag' => 'activitylog-migrations',
            ]);

            Artisan::call('migrate');
        }

    }

    public function shouldUseMysql(): bool
    {
        return property_exists($this, 'use_mysql') ? $this->use_mysql : false;
    }

    protected function getPackageProviders($app)
    {
        return [
            SettingsServiceProvider::class,
            HelpersServiceProvider::class,
            ActivitylogServiceProvider::class,
            ExcelServiceProvider::class,
            FormsServiceProvider::class,
            StatsServiceProvider::class,
            TestServiceProvider::class
        ];
    }
}
