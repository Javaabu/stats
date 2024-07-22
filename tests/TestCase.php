<?php

namespace Javaabu\Stats\Tests;

use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Javaabu\Stats\StatsServiceProvider;
use Javaabu\Stats\Tests\TestSupport\Providers\TestServiceProvider;
use Spatie\Activitylog\ActivitylogServiceProvider;

abstract class TestCase extends BaseTestCase
{

    public function setUp(): void
    {
        if (isset($_ENV['DB_CONNECTION']) || isset($_ENV['DB_DATABASE'])) {
            if (! $this->shouldUseMysql()) {
                $_ENV['DB_CONNECTION'] = 'sqlite';
                $_ENV['DB_DATABASE'] = ':memory:';
            }
        }

        parent::setUp();

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
            ActivitylogServiceProvider::class,
            StatsServiceProvider::class,
            TestServiceProvider::class
        ];
    }
}
