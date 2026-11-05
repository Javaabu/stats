<?php

namespace Javaabu\Stats\Tests\Feature\Charts;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Stats\Charts\CategoryChart;
use Javaabu\Stats\Charts\PieChart;
use Javaabu\Stats\Enums\CategoricalModes;
use Javaabu\Stats\Support\ExactDateRange;
use Javaabu\Stats\Tests\Concerns\InteractsWithStats;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Models\User;
use PHPUnit\Framework\Attributes\Test;

class CategoricalChartsTest extends TestCase
{
    use InteractsWithStats;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerTestStats();
    }

    protected function july(): ExactDateRange
    {
        return new ExactDateRange('2024-07-01', '2024-07-04 23:59:59');
    }

    #[Test]
    public function it_has_a_row_per_category_with_data(): void
    {
        [$ahmed, $aisha] = $this->seedPayments();

        $chart = CategoryChart::make('payments_by_user')->range($this->july())->toArray();

        $this->assertEquals('category', $chart['type']);
        $this->assertEquals('category', $chart['categoryKey']);
        $this->assertEquals('User', $chart['categoryLabel']);
        $this->assertEquals([
            ['category' => 'އަޙްމަދު', 'categoryId' => $ahmed->id, 'payments_by_user' => 3],
            ['category' => 'ޢާއިޝާ', 'categoryId' => $aisha->id, 'payments_by_user' => 2],
        ], $chart['data']);
        $this->assertEquals('bar', $chart['variant']);
        $this->assertEquals('vertical', $chart['orientation']);
    }

    #[Test]
    public function it_shows_every_category_or_the_chosen_ones(): void
    {
        [, $aisha] = $this->seedPayments();
        User::factory()->create(['name' => 'ޙަސަން']);

        $all = CategoryChart::make('payments_by_user')->range($this->july())->categoryMode(CategoricalModes::ALL)->toArray();
        $this->assertCount(3, $all['data']);
        $this->assertEquals(0, $all['data'][2]['payments_by_user']);

        $chosen = CategoryChart::make('payments_by_user')->range($this->july())->categories([$aisha->id])->toArray();
        $this->assertEquals(['ޢާއިޝާ'], array_column($chosen['data'], 'category'));
    }

    #[Test]
    public function it_keeps_the_top_categories(): void
    {
        $this->seedPayments();

        $chart = CategoryChart::make('payments_by_user')->range($this->july())->top(1)->horizontal()->toArray();

        $this->assertEquals(['އަޙްމަދު'], array_column($chart['data'], 'category'));
        $this->assertEquals('horizontal', $chart['orientation']);
    }

    #[Test]
    public function it_plots_several_categorical_stats_and_comparisons(): void
    {
        $this->seedPayments();

        $several = CategoryChart::make()->stats(['payments_by_user', 'payment_amounts_by_user'])->range($this->july())->line()->toArray();
        $this->assertEquals(['payments_by_user', 'payment_amounts_by_user'], array_keys($several['config']));
        $this->assertEquals(300, $several['data'][0]['payment_amounts_by_user']);
        $this->assertNull($several['orientation']);

        $compared = CategoryChart::make('payments_by_user')->range(new ExactDateRange('2024-07-03', '2024-07-04 23:59:59'))->compare()->toArray();
        $this->assertEquals('previous', $compared['comparison']['previousKey']);
        $this->assertEquals([0, 0], array_column($compared['data'], 'previous'));
    }

    #[Test]
    public function it_makes_pie_slices_largest_first_with_the_rest_as_other(): void
    {
        $this->seedPayments();

        $pie = PieChart::make('payments_by_user')->range($this->july())->donut()->top(1)->toArray();

        $this->assertEquals('pie', $pie['type']);
        $this->assertEquals('donut', $pie['variant']);
        $this->assertEquals([
            ['name' => 'އަޙްމަދު', 'value' => 3],
            ['name' => 'Other', 'value' => 2],
        ], array_map(fn ($slice) => ['name' => $slice['name'], 'value' => $slice['value']], $pie['data']));
        $this->assertEquals(5, $pie['total']);
        $this->assertNull($pie['centerLabel']);
    }

    #[Test]
    public function it_sets_the_donut_centre(): void
    {
        $this->seedPayments();

        $pie = PieChart::make('payments_by_user')->range($this->july())->donut()->centerLabel('MVR 4.2M', 'ބަޖެޓް')->toArray();
        $this->assertEquals(['value' => 'MVR 4.2M', 'label' => 'ބަޖެޓް'], $pie['centerLabel']);

        $none = PieChart::make('payments_by_user')->range($this->july())->donut()->centerLabel(false)->toArray();
        $this->assertFalse($none['centerLabel']);
    }
}
