<?php

namespace Javaabu\Stats\Tests\TestSupport\Providers;

use Illuminate\Support\ServiceProvider;

class TestServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->loadMigrationsFrom([
            __DIR__ . '/../database',
        ]);

        $this->loadViewsFrom(__DIR__ . '/../views', 'test');
    }

    /**
     * Register the application services.
     */
    public function register()
    {

    }
}
