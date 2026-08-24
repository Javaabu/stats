<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Javaabu\Stats\CategoricalStats;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        CategoricalStats::register([
        ]);
    }
}
