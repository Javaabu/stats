<?php

namespace Javaabu\Stats\Tests\Feature\Pages;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Inertia\Testing\AssertableInertia;
use Javaabu\Stats\Tests\Concerns\InteractsWithStats;
use Javaabu\Stats\Tests\TestCase;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\Test;

/**
 * The React pages get the same data as the Blade ones. UPDATE_FIXTURES=1
 * writes the props to tests/js/fixtures for the Vitest tests.
 */
#[RequiresMethod(InertiaServiceProvider::class, 'register')]
class InertiaPagesTest extends TestCase
{
    use InteractsWithStats;
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), InertiaServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('stats.views', 'inertia');
        $app['config']->set('inertia.testing.ensure_pages_exist', false);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->group(fn () => Route::stats());

        $this->registerTestStats();
        $this->allowViewingStats();
    }

    #[Test]
    public function it_renders_the_time_series_page_with_the_form_and_the_chart(): void
    {
        [$ahmed] = $this->seedPayments();
        app()->setLocale('dv');

        $response = $this->actingAs($ahmed)
            ->get('/stats/time-series?metric=payments_count&mode=day&date_range=custom&date_from=2024-07-01&date_to=2024-07-04&compare_to=previous')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('stats::TimeSeries/Index')
                ->where('form.method', 'GET')
                ->where('form.state.metric', 'payments_count')
                ->where('form.components.0.name', 'metric')
                ->where('chart.type', 'time-series')
                ->where('chart.data.2.payments_count', 3)
                ->where('chart.comparison.previousKey', 'previous')
                ->where('export.url', url('stats/time-series'))
                ->has('export.token')
                ->where('strings.title', 'ވަގުތާއެކު ތަފާސް ހިސާބު')
                ->etc());

        $this->writeFixture($response, 'time-series-props.json');
    }

    #[Test]
    public function it_renders_the_categorical_page_with_a_pie(): void
    {
        [$ahmed] = $this->seedPayments();

        $response = $this->actingAs($ahmed)
            ->get('/stats/categorical?metric=payments_by_user&mode=non_empty&date_range=custom&date_from=2024-07-01&date_to=2024-07-04&variant=pie')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('stats::Categorical/Index')
                ->where('chart.type', 'pie')
                ->where('chart.data.0.name', 'އަޙްމަދު')
                ->where('chart.total', 5)
                ->etc());

        $this->writeFixture($response, 'categorical-props.json');
    }

    #[Test]
    public function it_sends_the_errors_instead_of_a_chart(): void
    {
        [$ahmed] = $this->seedPayments();

        $this->actingAs($ahmed)
            ->get('/stats/time-series?metric=unknown')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('chart', null)
                ->where('export', null)
                ->has('errors.metric')
                ->etc());
    }

    protected function writeFixture(TestResponse $response, string $name): void
    {
        if (! getenv('UPDATE_FIXTURES')) {
            return;
        }

        file_put_contents(
            __DIR__ . '/../../js/fixtures/' . $name,
            json_encode($response->viewData('page')['props'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
        );
    }
}
