<?php

namespace Javaabu\Stats\Tests;

use Javaabu\Filters\FiltersServiceProvider;
use Javaabu\Forms\FormsServiceProvider;
use Javaabu\GeneratorHelpers\Testing\InteractsWithTestFiles;
use Javaabu\GeneratorHelpers\Testing\InteractsWithTestStubs;
use Javaabu\Manifest\ManifestServiceProvider;
use Javaabu\Permissions\PermissionsServiceProvider;
use Javaabu\Stats\CategoricalStats;
use Javaabu\Stats\Facades\Stats;
use Javaabu\Stats\StatsServiceProvider;
use Javaabu\Stats\Tests\TestSupport\Models\User;
use Javaabu\Stats\Tests\TestSupport\Providers\TestServiceProvider;
use Javaabu\Stats\TimeSeriesStats;
use Javaabu\Stencil\StencilServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

/**
 * Runs on SQLite in memory, or on the database in DB_CONNECTION (mysql, mariadb, pgsql)
 */
abstract class TestCase extends BaseTestCase
{
    use InteractsWithTestFiles;
    use InteractsWithTestStubs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadTestStubsFrom(__DIR__ . '/TestSupport/stubs');
    }

    protected function tearDown(): void
    {
        TimeSeriesStats::register([], false);
        CategoricalStats::register([], false);
        Stats::clearWarmables();

        parent::tearDown();
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:yWa/ByhLC/GUvfToOuaPD7zDwB64qkc/QkaQOrT5IpE=');
        $app['config']->set('session.serialization', 'php');
        $app['config']->set('stats.layout', 'test::layouts.admin');
        $app['config']->set('stats.default_stats', false);
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('cache.default', 'array');

        $connection = env('DB_CONNECTION', 'sqlite');

        if ($connection !== 'sqlite') {
            $app['config']->set('database.default', $connection);
            $app['config']->set("database.connections.{$connection}.database", env('DB_DATABASE', 'stats_test'));
            $app['config']->set("database.connections.{$connection}.username", env('DB_USERNAME', 'root'));
            $app['config']->set("database.connections.{$connection}.password", env('DB_PASSWORD', ''));
            $app['config']->set("database.connections.{$connection}.host", env('DB_HOST', '127.0.0.1'));
            $app['config']->set("database.connections.{$connection}.port", env('DB_PORT', $connection === 'pgsql' ? 5432 : 3306));
        }
    }

    protected function getPackageProviders($app): array
    {
        $providers = [
            PermissionServiceProvider::class,
            PermissionsServiceProvider::class,
            ManifestServiceProvider::class,
            FormsServiceProvider::class,
            StencilServiceProvider::class,
            FiltersServiceProvider::class,
        ];

        if (class_exists(ActivitylogServiceProvider::class)) {
            $providers[] = ActivitylogServiceProvider::class;
        }

        return [
            ...$providers,
            StatsServiceProvider::class,
            TestServiceProvider::class,
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    protected function addQueryArg(array $args, string $url): string
    {
        return $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($args);
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Stats' => Stats::class,
        ];
    }
}
