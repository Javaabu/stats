<?php

namespace Javaabu\Stats\Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Javaabu\Stats\Charts\StatTile;
use Javaabu\Stats\Charts\TimeSeriesChart;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Support\ExactDateRange;
use Javaabu\Stats\Tests\Concerns\InteractsWithStats;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Http\IslandStatsController;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\PaymentsCount;
use PHPUnit\Framework\Attributes\Test;

class RegressionsTest extends TestCase
{
    use InteractsWithStats;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerTestStats();
        $this->allowViewingStats();
    }

    #[Test]
    public function it_applies_form_filters_posted_to_the_export(): void
    {
        [$ahmed] = $this->seedPayments();
        Route::middleware('web')->group(fn () => Route::stats());

        $csv = $this->actingAs($ahmed)
            ->post('/stats/time-series', [
                'metric' => 'payments_by_status',
                'mode' => 'day',
                'date_from' => '2024-07-03',
                'date_to' => '2024-07-04',
                'status' => 'pending',
            ])
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('2024-07-03,0', $csv);
        $this->assertStringContainsString('2024-07-04,2', $csv);
    }

    #[Test]
    public function it_works_with_a_route_parameter_in_the_prefix(): void
    {
        [$ahmed, $aisha] = $this->seedPayments();
        Route::middleware('web')->group(fn () => Route::stats([
            'prefix' => 'users/{owner}/stats',
            'name' => 'users.stats',
            'controllers' => ['time_series' => IslandStatsController::class],
        ]));

        $this->actingAs($ahmed)
            ->get("/users/{$aisha->id}/stats/time-series?owner={$aisha->id}&metric=payments_count&mode=day&date_range=custom&date_from=2024-07-03&date_to=2024-07-04")
            ->assertOk()
            ->assertSee(url("users/{$aisha->id}/stats/time-series"), false)
            ->assertSee('4 Jul 24 · Payments: 2', false);
    }

    #[Test]
    public function it_filters_by_zero_and_false(): void
    {
        $this->seedPayments();

        $stat = new PaymentsCount(new ExactDateRange('2024-07-01', '2024-07-04 23:59:59'), ['user' => 0]);

        $this->assertEquals(0, $stat->total());
    }

    #[Test]
    public function it_checks_the_export_ability(): void
    {
        [$ahmed] = $this->seedPayments();
        Route::middleware('web')->group(fn () => Route::stats());
        Gate::before(fn ($user, string $ability) => $ability === 'export' ? false : null);

        $this->actingAs($ahmed)
            ->post('/stats/time-series', ['metric' => 'payments_count', 'mode' => 'day', 'date_from' => '2024-07-03', 'date_to' => '2024-07-04'])
            ->assertForbidden();
    }

    #[Test]
    public function it_keeps_the_metric_in_category_paging_links(): void
    {
        [$ahmed] = $this->seedPayments();
        Route::middleware('web')->group(fn () => Route::stats());

        $this->actingAs($ahmed)
            ->getJson('/stats/categorical/categories?filter[metric]=payments_by_user&per_page=1')
            ->assertOk()
            ->assertJsonPath('next_page_url', fn (string $url) => str_contains(urldecode($url), 'filter[metric]=payments_by_user'));
    }

    #[Test]
    public function it_refuses_unknown_time_series_types(): void
    {
        $this->artisan('stats:time-series', ['name' => 'Foo', 'model' => 'Payment', '--type' => 'foo'])
            ->expectsOutputToContain('Invalid time series stat type [foo]')
            ->assertFailed();
    }

    #[Test]
    public function it_computes_again_after_an_option_changes(): void
    {
        $this->seedPayments();

        $chart = TimeSeriesChart::make('payments_count')->range(new ExactDateRange('2024-07-01', '2024-07-04 23:59:59'))->withoutCache();
        $this->assertNull($chart->toArray()['comparison']);

        $chart->compare()->mode('week');
        $this->assertEquals('previous', $chart->toArray()['comparison']['previousKey']);
        $this->assertEquals('week', $chart->toArray()['mode']);
    }

    #[Test]
    public function it_keeps_a_tiles_link_and_texts_out_of_its_cache_key(): void
    {
        $this->seedPayments();

        StatTile::make('payments_count')->range(PresetDateRanges::THIS_MONTH)->href('http://cli.test/payments')->toArray();

        $tile = StatTile::make('payments_count')->range(PresetDateRanges::THIS_MONTH)->href('https://app.test/payments')->icon('credit-card');

        $this->assertTrue($tile->isWarm());
        $this->assertEquals('https://app.test/payments', $tile->toArray()['href']);
        $this->assertEquals('credit-card', $tile->toArray()['icon']);
    }
}
