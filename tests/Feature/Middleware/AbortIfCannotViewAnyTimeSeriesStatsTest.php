<?php

namespace Javaabu\Stats\Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Models\User;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogoutsWithPermission;
use Javaabu\Stats\TimeSeriesStats;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;

/** Needs spatie/laravel-activitylog, which is optional */
#[RequiresMethod(Activity::class, 'causer')]
class AbortIfCannotViewAnyTimeSeriesStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {

        parent::setUp();

        TimeSeriesStats::registerApiRoute();
    }

    #[Test]
    public function it_aborts_if_the_user_cannot_view_any_time_series_stats(): void
    {
        $user = User::factory()->make();

        TimeSeriesStats::register([
            'user_logouts_with_permission' => UserLogoutsWithPermission::class,
        ], false);

        Gate::define('view_stats', function (User $user) {
            return false;
        });

        // sanity check
        $this->assertFalse(TimeSeriesStats::canViewAny($user));

        $this->actingAs($user);

        $this->get($this->addQueryArg(
            [
                'metric' => 'user_logouts_with_permission',
                'format' => 'chartjs',
                'date_range' => 'today',
                'mode' => 'hour',
            ],
            '/stats/time-series'
        ))
            ->assertForbidden();
    }

    #[Test]
    public function it_allows_the_api_request_if_the_user_can_view_any_time_series_stats(): void
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->make();

        TimeSeriesStats::register([
            'user_logouts_with_permission' => UserLogoutsWithPermission::class,
        ], false);

        Gate::define('view_stats', function (User $user) {
            return true;
        });

        // sanity check
        $this->assertTrue(TimeSeriesStats::canViewAny($user));

        $this->actingAs($user);

        $this->get($this->addQueryArg(
            [
                'metric' => 'user_logouts_with_permission',
                'format' => 'chartjs',
                'date_range' => 'today',
                'mode' => 'hour',
            ],
            '/stats/time-series'
        ))
            ->assertSuccessful();
    }
}
