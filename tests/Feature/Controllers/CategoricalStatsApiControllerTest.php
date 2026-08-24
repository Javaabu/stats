<?php

namespace Javaabu\Stats\Tests\Feature\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Javaabu\Stats\CategoricalStats;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Factories\PaymentFactory;
use Javaabu\Stats\Tests\TestSupport\Models\User;
use Javaabu\Stats\Tests\TestSupport\Stats\Categorical\PaymentsByUser;
use Javaabu\Stats\Tests\TestSupport\Stats\Categorical\PaymentsByUserWithPermission;

class CategoricalStatsApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        CategoricalStats::registerApiRoutes();
    }

    public function test_it_can_generate_categorical_stats(): void
    {
        CategoricalStats::register([
            'payments_by_user' => PaymentsByUser::class,
        ], false);

        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);
        PaymentFactory::new()->withUser($alice)->count(2)->create(['paid_at' => '2024-07-03']);
        PaymentFactory::new()->withUser($bob)->count(3)->create(['paid_at' => '2024-07-04']);

        $this->actingAs($alice)
            ->get(add_query_arg([
                'metric' => 'payments_by_user',
                'mode' => 'all',
                'format' => 'chartjs',
                'date_from' => '2024-07-01',
                'date_to' => '2024-07-07',
            ], '/stats/categorical'))
            ->assertSuccessful()
            ->assertJsonFragment([
                'metric' => 'payments_by_user',
                'metric_name' => 'Payments By User',
                'mode' => 'all',
                'aggregate_field' => 'count',
                'aggregate_field_label' => 'Count',
                'format' => 'chartjs',
            ])
            ->assertJsonPath('result.labels', ['Alice', 'Bob'])
            ->assertJsonPath('result.stats', [2, 3]);
    }

    public function test_it_can_search_the_selected_metrics_category_provider(): void
    {
        CategoricalStats::register([
            'payments_by_user' => PaymentsByUser::class,
        ], false);

        $user = User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Bob']);

        $this->actingAs($user)
            ->get('/stats/categorical/categories?filter[metric]=payments_by_user&filter[search]=ali')
            ->assertSuccessful()
            ->assertJsonPath('data', [
                ['id' => $user->id, 'label' => 'Alice'],
            ])
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('total', 1);
    }

    public function test_category_search_uses_the_configured_page_size(): void
    {
        CategoricalStats::register([
            'payments_by_user' => PaymentsByUser::class,
        ], false);
        $this->app['config']->set('stats.categorical_items_per_page', 1);

        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);

        $this->actingAs($alice)
            ->get('/stats/categorical/categories?filter[metric]=payments_by_user&page=2')
            ->assertSuccessful()
            ->assertJsonPath('data', [
                ['id' => $bob->id, 'label' => 'Bob'],
            ])
            ->assertJsonPath('per_page', 1)
            ->assertJsonPath('current_page', 2)
            ->assertJsonPath('total', 2);
    }

    public function test_category_search_rejects_a_metric_hidden_from_the_current_user(): void
    {
        CategoricalStats::register([
            'payments_by_user' => PaymentsByUser::class,
            'restricted' => PaymentsByUserWithPermission::class,
        ], false);

        $user = User::factory()->create();
        Gate::define('view_stats', fn () => false);

        $this->actingAs($user)
            ->get('/stats/categorical/categories?filter[metric]=restricted')
            ->assertForbidden();
    }

    public function test_it_rejects_metrics_that_do_not_support_current_filters(): void
    {
        CategoricalStats::register([
            'payments_by_user' => PaymentsByUser::class,
        ], false);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/stats/categorical/categories?filter[metric]=payments_by_user&filters[unknown]=1')
            ->assertForbidden();
    }
}
