<?php

namespace Javaabu\Stats\Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Javaabu\Permissions\Permissions;
use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;
use Javaabu\Stats\Policies\StatsPolicy;
use Javaabu\Stats\Tests\Concerns\InteractsWithStats;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Http\IslandStatsController;
use Javaabu\Stats\Tests\TestSupport\Models\Admin;
use Javaabu\Stats\Tests\TestSupport\Models\User;
use Javaabu\Stats\Tests\TestSupport\Policies\DenyAllPolicy;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\PaymentsByStatusCount;
use Javaabu\Stats\TimeSeriesStats;
use PHPUnit\Framework\Attributes\Test;

class RoutesAndAuthorizationTest extends TestCase
{
    use InteractsWithStats;
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('auth.guards.web_admin', ['driver' => 'session', 'provider' => 'admins']);
        $app['config']->set('auth.providers.admins', ['driver' => 'eloquent', 'model' => Admin::class]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerTestStats();
    }

    #[Test]
    public function it_registers_the_pages_with_a_prefix_name_and_guard(): void
    {
        Route::middleware('web')->group(fn () => Route::stats([
            'prefix' => 'admin/stats',
            'name' => 'admin.stats',
            'guard' => 'web_admin',
        ]));
        Route::getRoutes()->refreshNameLookups();

        $this->assertEquals(url('admin/stats/time-series'), route('admin.stats.time-series.index'));
        $this->assertEquals(url('admin/stats/time-series'), route('admin.stats.time-series.export'));
        $this->assertEquals(url('admin/stats/categorical'), route('admin.stats.categorical.index'));
        $this->assertEquals(url('admin/stats/categorical/categories'), route('admin.stats.categorical.categories'));
        $this->assertEquals(['web', 'auth:web_admin'], Route::getRoutes()->getByName('admin.stats.time-series.index')->gatherMiddleware());

        Gate::define('view_stats', fn ($user) => $user instanceof Admin);
        TimeSeriesStats::register(['payments_by_status' => PaymentsByStatusCount::class], false);

        $admin = Admin::query()->forceCreate(['name' => 'ވެރިޔާ', 'email' => 'admin@example.com']);

        $this->actingAs($admin, 'web_admin')
            ->get('/admin/stats/time-series')
            ->assertOk()
            ->assertSee(url('admin/stats/time-series'), false);
    }

    #[Test]
    public function it_keeps_users_of_other_guards_out(): void
    {
        Route::middleware('web')->group(fn () => Route::stats(['prefix' => 'admin/stats', 'guard' => 'web_admin']));
        Gate::define('view_stats', fn () => true);

        $this->actingAs(User::query()->forceCreate(['name' => 'ބޭނުންކުރާ', 'email' => 'user@example.com']), 'web')
            ->getJson('/admin/stats/time-series')
            ->assertUnauthorized();
    }

    #[Test]
    public function it_registers_only_the_stats_asked_for(): void
    {
        Route::stats(['categorical' => false]);
        Route::statsApi(['time_series' => false, 'middleware' => ['api']]);
        Route::getRoutes()->refreshNameLookups();

        $this->assertTrue(Route::has('stats.time-series.index'));
        $this->assertFalse(Route::has('stats.categorical.index'));
        $this->assertFalse(Route::has('api.stats.time-series.index'));
        $this->assertTrue(Route::has('api.stats.categorical.index'));
        $this->assertTrue(Route::has('api.stats.categorical.categories'));
        $this->assertEquals(['api'], Route::getRoutes()->getByName('api.stats.categorical.index')->gatherMiddleware());
    }

    #[Test]
    public function it_uses_controllers_from_the_options_or_the_config(): void
    {
        Route::stats(['controllers' => ['time_series' => IslandStatsController::class]]);
        Route::getRoutes()->refreshNameLookups();

        $this->assertEquals(IslandStatsController::class . '@index', Route::getRoutes()->getByName('stats.time-series.index')->getActionName());

        config()->set('stats.controllers.time_series', IslandStatsController::class);
        Route::stats(['name' => 'other', 'prefix' => 'other']);
        Route::getRoutes()->refreshNameLookups();

        $this->assertEquals(IslandStatsController::class . '@export', Route::getRoutes()->getByName('other.time-series.export')->getActionName());
    }

    #[Test]
    public function it_adds_the_controllers_fixed_filters(): void
    {
        [$ahmed, $aisha] = $this->seedPayments();
        $this->allowViewingStats();

        Route::middleware('web')->group(fn () => Route::stats(['controllers' => ['time_series' => IslandStatsController::class]]));

        $this->actingAs($ahmed)
            ->get("/stats/time-series?owner={$aisha->id}&metric=payments_count&mode=day&date_range=custom&date_from=2024-07-03&date_to=2024-07-04")
            ->assertOk()
            ->assertSee('name="filters[user]"', false)
            ->assertSee('3 Jul 24 · Payments: 0', false)
            ->assertSee('4 Jul 24 · Payments: 2', false);
    }

    #[Test]
    public function it_registers_the_view_stats_permission_and_the_policy(): void
    {
        $this->assertTrue(Permissions::has(StatsPolicy::VIEW));
        $this->assertEquals('View stats', Permissions::description(StatsPolicy::VIEW));
        $this->assertEquals(StatsPolicy::class, Permissions::policies()[TimeSeriesStatsRepository::class]);
    }

    #[Test]
    public function it_lets_stats_through_the_policy(): void
    {
        $user = User::factory()->create();
        $stat = new PaymentsByStatusCount;

        $this->assertFalse($stat->canView($user));
        $this->assertFalse($stat->canView(null));

        Gate::define('view_stats', fn () => true);

        $this->assertTrue($stat->canView($user));
        $this->assertTrue(Gate::forUser($user)->allows('export', $stat));
    }

    #[Test]
    public function it_lets_the_app_replace_the_policy(): void
    {
        Gate::define('view_stats', fn () => true);
        Gate::policy(TimeSeriesStatsRepository::class, DenyAllPolicy::class);

        $this->assertFalse((new PaymentsByStatusCount)->canView(User::factory()->create()));
    }
}
