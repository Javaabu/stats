<?php

namespace Javaabu\Stats\Tests\Feature;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Javaabu\Stats\Charts\StatTile;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Support\ExactDateRange;
use Javaabu\Stats\Support\TimeSeriesSteps;
use Javaabu\Stats\Tests\Concerns\InteractsWithStats;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\PaymentsCount;
use PHPUnit\Framework\Attributes\Test;

/**
 * Apps on Laravel's starter kits call Date::use(CarbonImmutable::class) (#14).
 * CI also runs the whole suite that way (STATS_IMMUTABLE_DATES=1).
 */
class ImmutableDatesTest extends TestCase
{
    use InteractsWithStats;
    use RefreshDatabase;

    protected bool $immutable_dates = true;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->group(fn () => Route::stats());

        $this->registerTestStats();
        $this->allowViewingStats();
    }

    #[Test]
    public function it_gives_preset_and_exact_ranges_in_the_apps_date_class(): void
    {
        $this->travelTo('2024-07-04 12:00:00');

        $this->assertInstanceOf(CarbonImmutable::class, now());

        foreach (PresetDateRanges::cases() as $range) {
            $this->assertInstanceOf(CarbonImmutable::class, $range->getDateFrom(), $range->value);
            $this->assertInstanceOf(CarbonImmutable::class, $range->getDateTo(), $range->value);
            $this->assertInstanceOf(CarbonImmutable::class, $range->getPreviousDateRange()->getDateFrom(), $range->value);
        }

        $this->assertSame('2024-06-28 00:00:00', PresetDateRanges::LAST_7_DAYS->getDateFrom()->toDateTimeString());
        $this->assertSame('2024-06-21 00:00:00', PresetDateRanges::LAST_7_DAYS->getPreviousDateRange()->getDateFrom()->toDateTimeString());
        $this->assertSame('2024-06-27 23:59:59', PresetDateRanges::LAST_7_DAYS->getPreviousDateRange()->getDateTo()->toDateTimeString());

        // A mutable date passed in is read, not kept
        $from = Carbon::parse('2024-07-01');
        $range = new ExactDateRange($from, '2024-07-04 23:59:59');
        $from->addYear();

        $this->assertInstanceOf(CarbonImmutable::class, $range->getDateFrom());
        $this->assertSame('2024-07-01 00:00:00', $range->getDateFrom()->toDateTimeString());
        $this->assertSame('2024-06-27 00:00:00', $range->getPreviousDateRange()->getDateFrom()->toDateTimeString());
    }

    #[Test]
    public function it_walks_the_steps_without_changing_the_stats_dates(): void
    {
        $this->seedPayments();

        $stat = new PaymentsCount(new ExactDateRange('2024-06-29', '2024-07-04 23:59:59'));

        $this->assertSame(5, $stat->interval(TimeSeriesModes::DAY));

        $steps = TimeSeriesSteps::for($stat, TimeSeriesModes::DAY);

        $this->assertSame(
            ['2024-06-29' => 1, '2024-06-30' => 0, '2024-07-01' => 0, '2024-07-02' => 0, '2024-07-03' => 3, '2024-07-04' => 2],
            collect($steps)->pluck('value', 'key')->all()
        );
        $this->assertInstanceOf(CarbonImmutable::class, $steps[0]['date']);

        $weeks = TimeSeriesSteps::for($stat, TimeSeriesModes::WEEK);

        $this->assertSame(['2024-06-23' => 1, '2024-06-30' => 5], collect($weeks)->pluck('value', 'key')->all());

        $this->assertSame('2024-06-29 00:00:00', $stat->getDateFrom()->toDateTimeString());
        $this->assertSame('2024-06-29 00:00:00 - 2024-07-04 23:59:59', $stat->formattedDateRange('YYYY-MM-DD HH:mm:ss'));
        $this->assertInstanceOf(CarbonImmutable::class, $stat->getMinDate());
    }

    #[Test]
    public function it_shows_the_stats_pages_charts_and_tiles(): void
    {
        [$ahmed] = $this->seedPayments();

        $this->actingAs($ahmed)
            ->get('/stats/time-series')
            ->assertOk()
            ->assertSee('<svg class="stats-chart__svg"', false);

        $this->actingAs($ahmed)
            ->get('/stats/time-series?metric=payments_count&mode=week&date_range=custom&date_from=2024-07-01&date_to=2024-07-04&compare_to=previous')
            ->assertOk()
            ->assertSee('data-slot="period-current-total">5<', false)
            ->assertSee('data-slot="period-previous-total">1<', false);

        $this->actingAs($ahmed)
            ->get('/stats/categorical?metric=payments_by_user&mode=non_empty&date_range=last_7_days&compare_to=previous')
            ->assertOk()
            ->assertSee('ޢާއިޝާ · Last 7 days: 3', false)
            ->assertSee('ޢާއިޝާ · 21 Jun 2024 – 27 Jun 2024: 0', false);

        $csv = $this->actingAs($ahmed)
            ->post('/stats/time-series', [
                'metric' => 'payments_count',
                'mode' => 'day',
                'date_from' => '2024-07-03',
                'date_to' => '2024-07-04',
            ])
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('2024-07-03,3', $csv);
        $this->assertStringContainsString('2024-07-04,2', $csv);

        $tile = StatTile::make('payments_count')->range(PresetDateRanges::THIS_WEEK)->compare()->sparkline()->toArray();

        $this->assertSame(5, $tile['value']);
    }

    #[Test]
    public function it_serves_the_json_api(): void
    {
        [$ahmed] = $this->seedPayments();
        Route::middleware('web')->group(fn () => Route::statsApi(['prefix' => 'api/stats']));

        $this->actingAs($ahmed)
            ->getJson('/api/stats/time-series?metric=payments_count&mode=hour&date_range=today&format=default')
            ->assertOk();

        $this->actingAs($ahmed)
            ->getJson('/api/stats/time-series?metric=payments_count&mode=month&date_range=lifetime&format=chartjs&compare_date_range=last_year')
            ->assertOk();
    }
}
