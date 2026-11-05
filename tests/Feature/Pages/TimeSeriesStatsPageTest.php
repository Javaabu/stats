<?php

namespace Javaabu\Stats\Tests\Feature\Pages;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Javaabu\Stats\Tests\Concerns\InteractsWithStats;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Models\User;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\PaymentsByStatusCount;
use Javaabu\Stats\TimeSeriesStats;
use PHPUnit\Framework\Attributes\Test;

class TimeSeriesStatsPageTest extends TestCase
{
    use InteractsWithStats;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->group(fn () => Route::stats());

        $this->registerTestStats();
        $this->allowViewingStats();
    }

    #[Test]
    public function it_shows_the_form_and_a_chart_of_the_first_stat_by_default(): void
    {
        [$ahmed] = $this->seedPayments();

        $this->actingAs($ahmed)
            ->get('/stats/time-series')
            ->assertOk()
            ->assertSee('data-stats-page="time-series"', false)
            ->assertSee('name="metric"', false)
            ->assertSee('name="mode"', false)
            ->assertSee('name="date_range"', false)
            ->assertSee('name="compare_to"', false)
            ->assertSee('data-slot="chart"', false)
            ->assertSee('<svg class="stats-chart__svg"', false)
            ->assertSee('View as table');
    }

    #[Test]
    public function it_draws_the_chosen_stat_for_the_chosen_range(): void
    {
        [$ahmed] = $this->seedPayments();

        $response = $this->actingAs($ahmed)
            ->get('/stats/time-series?metric=payments_count&mode=day&date_range=custom&date_from=2024-07-01&date_to=2024-07-04')
            ->assertOk();

        $response->assertSeeInOrder(['1 Jul 24', '2 Jul 24', '3 Jul 24', '4 Jul 24']);
        $response->assertSee('3 Jul 24 · Payments: 3', false);
        $response->assertSee('4 Jul 24 · Payments: 2', false);
        $response->assertSee('Line chart with 1 series and 4 data points.');
        $response->assertSee('Payments: lowest 0, highest 3.');
    }

    #[Test]
    public function it_compares_the_stat_with_the_previous_period(): void
    {
        [$ahmed] = $this->seedPayments();

        $this->actingAs($ahmed)
            ->get('/stats/time-series?metric=payments_count&mode=day&date_range=custom&date_from=2024-07-01&date_to=2024-07-04&compare_to=previous')
            ->assertOk()
            ->assertSee('data-slot="period-totals"', false)
            ->assertSee('data-slot="period-current-total">5<', false)
            ->assertSee('data-slot="period-previous-total">1<', false)
            ->assertSee('data-direction="up"', false)
            ->assertSee('Up 400%');
    }

    #[Test]
    public function it_plots_other_stats_on_the_same_chart(): void
    {
        [$ahmed] = $this->seedPayments();

        $this->actingAs($ahmed)
            ->get('/stats/time-series?metric=payments_count&with[]=payments_sum&mode=day&date_range=custom&date_from=2024-07-03&date_to=2024-07-04&variant=bar')
            ->assertOk()
            ->assertSee('data-key="payments_count"', false)
            ->assertSee('data-key="payments_sum"', false)
            ->assertSee('data-slot="chart-legend"', false)
            ->assertSee('Bar chart with 2 series and 2 data points.');
    }

    #[Test]
    public function it_filters_the_stat_with_its_filters_from_the_query_string(): void
    {
        [$ahmed] = $this->seedPayments();

        $this->actingAs($ahmed)
            ->get('/stats/time-series?metric=payments_by_status&mode=day&date_range=custom&date_from=2024-07-03&date_to=2024-07-04&status=pending')
            ->assertOk()
            ->assertSee('name="status"', false)
            ->assertSee('ހާލަތު')
            ->assertSee('3 Jul 24 · ފައިސާ ދެއްކުން: 0', false)
            ->assertSee('4 Jul 24 · ފައިސާ ދެއްކުން: 2', false);
    }

    #[Test]
    public function it_shows_the_errors_instead_of_a_chart_for_invalid_input(): void
    {
        [$ahmed] = $this->seedPayments();

        $this->actingAs($ahmed)
            ->get('/stats/time-series?metric=unknown&date_range=custom')
            ->assertOk()
            ->assertDontSee('data-slot="chart"', false)
            ->assertSee('Fix the errors in the form to see the stat.');
    }

    #[Test]
    public function it_forbids_users_who_cannot_view_any_stat(): void
    {
        Gate::define('view_stats', fn () => false);

        TimeSeriesStats::register(['payments_by_status' => PaymentsByStatusCount::class], false);

        $this->actingAs(User::factory()->create())
            ->get('/stats/time-series')
            ->assertForbidden();
    }

    #[Test]
    public function it_needs_a_logged_in_user(): void
    {
        $this->getJson('/stats/time-series')->assertUnauthorized();
    }

    #[Test]
    public function it_downloads_the_stat_as_csv(): void
    {
        [$ahmed] = $this->seedPayments();
        config()->set('app.name', 'test app');

        $response = $this->actingAs($ahmed)
            ->post('/stats/time-series', [
                'metric' => 'payments_count',
                'mode' => 'day',
                'date_from' => '2024-07-03',
                'date_to' => '2024-07-04',
                'compare_to' => 'previous',
            ])
            ->assertOk()
            ->assertDownload('Test App Payments 20240703-20240704 20240701-20240702.csv');

        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\u{FEFF}", $csv);
        $this->assertStringContainsString("Day,\"Date Range\",Count\n", $csv);
        $this->assertStringContainsString('2024-07-03,"2024-07-03 00:00 - 2024-07-04 23:59",3', $csv);
        $this->assertStringContainsString('2024-07-04,"2024-07-03 00:00 - 2024-07-04 23:59",2', $csv);
    }

    #[Test]
    public function it_validates_the_export(): void
    {
        [$ahmed] = $this->seedPayments();

        $this->actingAs($ahmed)
            ->post('/stats/time-series', ['metric' => 'unknown', 'mode' => 'day'])
            ->assertSessionHasErrors(['metric', 'date_from', 'date_to']);
    }
}
