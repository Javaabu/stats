<?php

namespace Javaabu\Stats\Tests\Feature\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Stats\CategoricalStats;
use Javaabu\Stats\Exports\CategoricalStatsExport;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Factories\PaymentFactory;
use Javaabu\Stats\Tests\TestSupport\Models\User;
use Javaabu\Stats\Tests\TestSupport\Stats\Categorical\PaymentsByUser;
use Maatwebsite\Excel\Facades\Excel;

class CategoricalStatsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('stats.default_layout', 'test::layouts.admin');
        CategoricalStats::registerApiRoutes('/api/stats/categorical');
        CategoricalStats::registerRoutes(middleware: ['web', 'stats.view-categorical']);
        CategoricalStats::register([
            'payments_by_user' => PaymentsByUser::class,
        ], false);
    }

    public function test_it_can_display_the_categorical_stats_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/stats/categorical')
            ->assertSuccessful()
            ->assertSee('name="metric"', false)
            ->assertSee('name="categorical_values[]"', false)
            ->assertSee('id="categorical-metric"', false)
            ->assertSee('id="categorical-mode"', false)
            ->assertSee('id="categorical-values"', false)
            ->assertSee('id="categorical-custom-date-range"', false)
            ->assertSee('id="categorical-generate-graph"', false)
            ->assertSee('id="categorical-download-stats"', false)
            ->assertSee("$('#categorical-generate-graph')", false)
            ->assertSee("$('#categorical-download-stats')", false)
            ->assertDontSee('id="generate-graph"', false)
            ->assertDontSee('id="btn-download-stats"', false)
            ->assertDontSee("$('#generate-graph')", false)
            ->assertDontSee("$('#btn-download-stats')", false);
    }

    public function test_it_can_export_categorical_stats(): void
    {
        $user = User::factory()->create(['name' => 'Alice']);
        PaymentFactory::new()->withUser($user)->count(2)->create(['paid_at' => '2024-07-03']);
        Excel::fake();
        $this->setFakeSetting('app_name', 'Test App');

        $this->actingAs($user)
            ->post('/stats/categorical', [
                'metric' => 'payments_by_user',
                'mode' => 'all',
                'date_from' => '2024-07-01',
                'date_to' => '2024-07-07',
            ])
            ->assertSuccessful();

        Excel::assertDownloaded('Test App Payments By User 20240701-20240707.csv', function (CategoricalStatsExport $export) {
            return $export->getReportTitle() == 'Payments By User'
                && $export->headings()[0] == 'User ID'
                && $export->headings()[1] == 'User';
        });
    }
}
