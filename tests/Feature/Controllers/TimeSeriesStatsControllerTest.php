<?php

namespace Javaabu\Stats\Tests\Feature\Controllers;

use Illuminate\Support\Facades\Gate;
use Javaabu\Stats\Exports\TimeSeriesStatsExport;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Factories\ActivityFactory;
use Javaabu\Stats\Tests\TestSupport\Models\User;
use Javaabu\Stats\Tests\TestSupport\MySQLRefreshDatabase;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\TestUserLoginsRepository;
use Javaabu\Stats\TimeSeriesStats;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;

class TimeSeriesStatsControllerTest extends TestCase
{
    use MySQLRefreshDatabase;

    public function setUp(): void
    {
        $this->setupMySql();

        parent::setUp();

        $this->app['config']->set('stats.default_layout', 'test::layouts.admin');

        TimeSeriesStats::registerApiRoute('/api/stats/time-series');
        TimeSeriesStats::registerRoutes(middleware: ['web', 'stats.view-time-series']);

        Activity::query()->delete();
    }

    /** @test */
    public function it_can_display_the_time_series_stats_generation_form(): void
    {
        $this->withoutExceptionHandling();

        $this->travelTo('2024-07-04');

        TimeSeriesStats::register([
            'user_logins' => TestUserLoginsRepository::class
        ], false);

        Gate::define('view_stats', function (User $user) {
            return true;
        });

        // yesterday
        ActivityFactory::new()
            ->login()
            ->withUser()
            ->count(5)
            ->create([
                'created_at' => '2024-07-03',
            ]);

        $user = User::factory()->create();

        // today
        ActivityFactory::new()
            ->login()
            ->withUser($user)
            ->count(2)
            ->create([
                'created_at' => '2024-07-04',
            ]);

        $this->actingAs($user);

        $this->get('/stats/time-series')
            ->assertSuccessful()
            ->assertSee('name="metric"', false);
    }

    /** @test */
    public function it_can_export_time_series_stats_with_preset_date_range(): void
    {
        $this->withoutExceptionHandling();

        $this->travelTo('2024-07-04');

        TimeSeriesStats::register([
            'user_logins' => TestUserLoginsRepository::class
        ], false);

        Gate::define('view_stats', function (User $user) {
            return true;
        });

        // yesterday
        ActivityFactory::new()
            ->login()
            ->withUser()
            ->count(5)
            ->create([
                'created_at' => '2024-07-03',
            ]);

        // today
        ActivityFactory::new()
            ->login()
            ->withUser()
            ->count(2)
            ->create([
                'created_at' => '2024-07-04',
            ]);

        $user = User::factory()->create();

        Excel::fake();

        $this->setFakeSetting('app_name', 'Test App');

        $this->actingAs($user);

        $this->post(
            '/stats/time-series',
            [
                'metric' => 'user_logins',
                'date_range' => 'last_7_days',
                'date_from' => '',
                'date_to' => '',
                'mode' => 'day',
                'compare' => true,
                'compare_date_from' => '',
                'compare_date_to' => '',
            ]
        )
            ->assertSuccessful();

        Excel::assertDownloaded('Test App Test User Logins 20240628-20240704 20240621-20240627.csv', function(TimeSeriesStatsExport $export) {
            return $export->getReportTitle() == 'Test User Logins';
        });
    }
}
