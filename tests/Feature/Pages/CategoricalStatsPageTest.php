<?php

namespace Javaabu\Stats\Tests\Feature\Pages;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Javaabu\Stats\Tests\Concerns\InteractsWithStats;
use Javaabu\Stats\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CategoricalStatsPageTest extends TestCase
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
    public function it_shows_the_categorical_form_and_a_bar_chart(): void
    {
        [$ahmed] = $this->seedPayments();

        $this->actingAs($ahmed)
            ->get('/stats/categorical?metric=payments_by_user&mode=non_empty&date_range=custom&date_from=2024-07-01&date_to=2024-07-04')
            ->assertOk()
            ->assertSee('data-stats-page="categorical"', false)
            ->assertSee('name="categorical_values[]"', false)
            ->assertSee('filter-field="metric"', false)
            ->assertSee('/stats/categorical/categories', false)
            ->assertSee('data-chart-variant="bar"', false)
            ->assertSee('އަޙްމަދު · Payments By User: 3', false)
            ->assertSee('ޢާއިޝާ · Payments By User: 2', false);
    }

    #[Test]
    public function it_draws_a_pie_of_one_stat(): void
    {
        [$ahmed] = $this->seedPayments();

        $this->actingAs($ahmed)
            ->get('/stats/categorical?metric=payments_by_user&mode=non_empty&date_range=custom&date_from=2024-07-01&date_to=2024-07-04&variant=pie')
            ->assertOk()
            ->assertSee('data-chart-variant="donut"', false)
            ->assertSee('Donut chart with 2 slices. Total 5. Largest: އަޙްމަދު, 60%.');
    }

    #[Test]
    public function it_compares_the_categories_with_the_previous_period(): void
    {
        [$ahmed] = $this->seedPayments();

        $this->actingAs($ahmed)
            ->get('/stats/categorical?metric=payments_by_user&mode=all&date_range=custom&date_from=2024-07-03&date_to=2024-07-04&compare_to=previous')
            ->assertOk()
            ->assertSee('data-key="previous"', false)
            ->assertSee('data-slot="period-current-total">5<', false);
    }

    #[Test]
    public function it_shows_only_the_chosen_categories(): void
    {
        [$ahmed, $aisha] = $this->seedPayments();

        $this->actingAs($ahmed)
            ->get("/stats/categorical?metric=payments_by_user&mode=specific&categorical_values[]={$aisha->id}&date_range=custom&date_from=2024-07-01&date_to=2024-07-04")
            ->assertOk()
            ->assertSee('ޢާއިޝާ · Payments By User: 2', false)
            ->assertDontSee('އަޙްމަދު · Payments By User', false);
    }

    #[Test]
    public function it_searches_the_categories_of_a_stat(): void
    {
        [$ahmed] = $this->seedPayments();

        $this->actingAs($ahmed)
            ->getJson($this->addQueryArg(['filter' => ['metric' => 'payments_by_user', 'search' => 'ޢާއި']], '/stats/categorical/categories'))
            ->assertOk()
            ->assertJsonPath('data.0.label', 'ޢާއިޝާ')
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function it_downloads_the_categorical_stat_as_csv(): void
    {
        [$ahmed] = $this->seedPayments();
        config()->set('app.name', 'Test App');

        $response = $this->actingAs($ahmed)
            ->post('/stats/categorical', [
                'metric' => 'payments_by_user',
                'mode' => 'all',
                'date_from' => '2024-07-01',
                'date_to' => '2024-07-07',
            ])
            ->assertOk()
            ->assertDownload('Test App Payments By User 20240701-20240707.csv');

        $csv = $response->streamedContent();

        $this->assertStringContainsString("\"User ID\",User,Count\n", $csv);
        $this->assertStringContainsString("{$ahmed->id},އަޙްމަދު,3", $csv);
    }
}
