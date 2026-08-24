<?php

namespace Javaabu\Stats\Tests\Feature\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Stats\Enums\CategoricalModes;
use Javaabu\Stats\Exports\CategoricalStatsExport;
use Javaabu\Stats\Formatters\Categorical\ChartjsCategoricalStatsFormatter;
use Javaabu\Stats\Support\ExactDateRange;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Factories\PaymentFactory;
use Javaabu\Stats\Tests\TestSupport\Models\User;
use Javaabu\Stats\Tests\TestSupport\Stats\Categorical\PaymentAmountsByUser;
use Javaabu\Stats\Tests\TestSupport\Stats\Categorical\PaymentsByUser;

class CategoricalStatsRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_groups_results_by_category_and_applies_dates_and_filters(): void
    {
        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);

        PaymentFactory::new()->withUser($alice)->count(2)->create(['paid_at' => '2024-07-03']);
        PaymentFactory::new()->withUser($bob)->count(3)->create(['paid_at' => '2024-07-04']);
        PaymentFactory::new()->withUser($alice)->create(['paid_at' => '2024-06-01']);

        $stats = new PaymentsByUser(
            new ExactDateRange('2024-07-01', '2024-07-07'),
            ['user' => $alice->id]
        );

        $this->assertEquals([
            $alice->id => 2,
        ], $stats->resultsToArray($stats->results()));
    }

    public function test_all_mode_includes_categories_without_results(): void
    {
        $alice = User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Bob']);
        PaymentFactory::new()->withUser($alice)->count(2)->create(['paid_at' => '2024-07-03']);

        $stats = new PaymentsByUser(new ExactDateRange('2024-07-01', '2024-07-07'));
        $result = (new ChartjsCategoricalStatsFormatter)->format($stats, mode: CategoricalModes::ALL);

        $this->assertEquals(['Alice', 'Bob'], $result['labels']);
        $this->assertEquals([2, 0], $result['stats']);
    }

    public function test_specific_mode_only_includes_requested_categories(): void
    {
        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);
        PaymentFactory::new()->withUser($alice)->create(['paid_at' => '2024-07-03']);
        PaymentFactory::new()->withUser($bob)->count(2)->create(['paid_at' => '2024-07-03']);

        $stats = new PaymentsByUser(new ExactDateRange('2024-07-01', '2024-07-07'));
        $result = (new ChartjsCategoricalStatsFormatter)->format(
            $stats,
            mode: CategoricalModes::SPECIFIC,
            categorical_values: [$bob->id]
        );

        $this->assertEquals(['Bob'], $result['labels']);
        $this->assertEquals([2], $result['stats']);
    }

    public function test_it_can_sum_results_by_category(): void
    {
        $alice = User::factory()->create(['name' => 'Alice']);
        PaymentFactory::new()->withUser($alice)->create([
            'amount' => 12.50,
            'paid_at' => '2024-07-03',
        ]);
        PaymentFactory::new()->withUser($alice)->create([
            'amount' => 7.25,
            'paid_at' => '2024-07-04',
        ]);

        $stats = new PaymentAmountsByUser(new ExactDateRange('2024-07-01', '2024-07-07'));

        $this->assertSame(19.75, $stats->resultsToArray($stats->results())[$alice->id]);
    }

    public function test_it_can_group_and_order_by_a_category_expression(): void
    {
        PaymentFactory::new()->count(2)->create([
            'amount' => 5,
            'paid_at' => '2024-07-03',
        ]);
        PaymentFactory::new()->create([
            'amount' => 15,
            'paid_at' => '2024-07-04',
        ]);

        $stats = new class(new ExactDateRange('2024-07-01', '2024-07-07')) extends PaymentsByUser
        {
            public function getCategoryField(): string
            {
                return "CASE WHEN payments.amount >= 10 THEN 'large' ELSE 'small' END";
            }
        };

        $this->assertSame([
            'large' => 1,
            'small' => 2,
        ], $stats->resultsToArray($stats->results()));
    }

    public function test_it_can_customize_the_grouped_category_field_alias(): void
    {
        $alice = User::factory()->create(['name' => 'Alice']);
        PaymentFactory::new()->withUser($alice)->count(2)->create(['paid_at' => '2024-07-03']);

        $stats = new class(new ExactDateRange('2024-07-01', '2024-07-07')) extends PaymentsByUser
        {
            public function getCategoryFieldAlias(): string
            {
                return 'user';
            }

            public function getCategoryNameFieldAlias(): string
            {
                return 'display_name';
            }

            public function getCategoryTitle(): string
            {
                return 'Customer';
            }

            public function getCategoryIdTitle(): string
            {
                return 'Customer Number';
            }
        };

        $results = $stats->results();

        $this->assertSame($alice->id, $results->first()->getAttribute('user'));
        $this->assertNull($results->first()->getAttribute('category'));
        $this->assertSame([$alice->id => 2], $stats->resultsToArray($results));

        $formatted = (new ChartjsCategoricalStatsFormatter)->format($stats);

        $this->assertSame(['Alice'], $formatted['labels']);
        $this->assertSame([2], $formatted['stats']);

        $export = new CategoricalStatsExport($stats);
        $rows = $export->array();

        $this->assertSame([
            'user' => $alice->id,
            'display_name' => 'Alice',
            'count' => 2,
            'compare_count' => null,
        ], $rows[0]);
        $this->assertSame(['Customer Number', 'Customer', 'Count'], $export->headings());
        $this->assertSame([$alice->id, 'Alice', 2], $export->map($rows[0]));
    }
}
