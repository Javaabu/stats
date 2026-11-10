<?php

namespace Javaabu\Stats\Tests\Feature\Charts;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Stats\Charts\TimeSeriesChart;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Exceptions\StatsException;
use Javaabu\Stats\Support\ExactDateRange;
use Javaabu\Stats\Tests\Concerns\InteractsWithStats;
use Javaabu\Stats\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TimeSeriesChartTest extends TestCase
{
    use InteractsWithStats;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerTestStats();
    }

    #[Test]
    public function it_has_one_row_per_step_with_zero_for_steps_without_data(): void
    {
        $this->seedPayments();

        $chart = TimeSeriesChart::make('payments_count')
            ->mode(TimeSeriesModes::DAY)
            ->range(new ExactDateRange('2024-07-01', '2024-07-04 23:59:59'))
            ->toArray();

        $this->assertEquals('time-series', $chart['type']);
        $this->assertEquals('Payments', $chart['title']);
        $this->assertEquals([
            ['date' => '2024-07-01', 'payments_count' => 0],
            ['date' => '2024-07-02', 'payments_count' => 0],
            ['date' => '2024-07-03', 'payments_count' => 3],
            ['date' => '2024-07-04', 'payments_count' => 2],
        ], $chart['data']);
        $this->assertEquals(['1 Jul 24', '2 Jul 24', '3 Jul 24', '4 Jul 24'], $chart['labels']);
        $this->assertEquals(['payments_count' => ['label' => 'Payments']], $chart['config']);
        $this->assertEquals('day', $chart['interval']);
        $this->assertEquals(['payments_count' => 5], $chart['totals']);
        $this->assertNull($chart['comparison']);
    }

    #[Test]
    public function it_plots_several_stats_with_their_own_labels(): void
    {
        $this->seedPayments();

        $chart = TimeSeriesChart::make()
            ->stats(['payments_count' => 'ފައިސާ', 'payments_sum'])
            ->mode('day')
            ->range(new ExactDateRange('2024-07-03', '2024-07-04 23:59:59'))
            ->bar()
            ->stacked()
            ->toArray();

        $this->assertEquals(['payments_count', 'payments_sum'], array_keys($chart['config']));
        $this->assertEquals('ފައިސާ', $chart['config']['payments_count']['label']);
        $this->assertEquals(300, $chart['data'][0]['payments_sum']);
        $this->assertEquals('bar', $chart['variant']);
        $this->assertTrue($chart['stacked']);
    }

    #[Test]
    public function it_compares_the_first_stat_with_the_previous_period(): void
    {
        $this->seedPayments();

        $chart = TimeSeriesChart::make('payments_count')
            ->mode(TimeSeriesModes::DAY)
            ->range(new ExactDateRange('2024-07-03', '2024-07-04 23:59:59'))
            ->compare()
            ->positiveIsGood(false)
            ->toArray();

        $this->assertEquals(['payments_count', 'previous'], array_keys($chart['config']));
        $this->assertEquals(['date' => '2024-07-03', 'payments_count' => 3, 'previous' => 0], $chart['data'][0]);
        $this->assertEquals('previous', $chart['comparison']['previousKey']);
        $this->assertEquals('3 Jul 2024 – 4 Jul 2024', $chart['comparison']['currentLabel']);
        $this->assertEquals('1 Jul 2024 – 2 Jul 2024', $chart['comparison']['previousLabel']);
        $this->assertFalse($chart['comparison']['positiveIsGood']);
    }

    #[Test]
    public function it_plots_one_stat_over_several_periods(): void
    {
        $this->seedPayments();

        $chart = TimeSeriesChart::make('payments_count')
            ->mode(TimeSeriesModes::MONTH)
            ->periods([PresetDateRanges::THIS_YEAR, PresetDateRanges::LAST_YEAR])
            ->toArray();

        $this->assertEquals(['period_1', 'period_2'], array_keys($chart['config']));
        $this->assertEquals('This year', $chart['config']['period_1']['label']);
        $this->assertEquals('Last year', $chart['config']['period_2']['label']);
        $this->assertCount(12, $chart['data']);
        $this->assertEquals(['date' => '2024-07-01', 'period_1' => 5, 'period_2' => 0], $chart['data'][6]);
        $this->assertEquals(1, $chart['data'][5]['period_1']);
    }

    #[Test]
    public function it_groups_by_week_and_year(): void
    {
        $this->seedPayments();

        $weeks = TimeSeriesChart::make('payments_count')->mode(TimeSeriesModes::WEEK)->range(new ExactDateRange('2024-06-23', '2024-07-06 23:59:59'))->toArray();

        $this->assertEquals([
            ['date' => '2024-06-23', 'payments_count' => 1],
            ['date' => '2024-06-30', 'payments_count' => 5],
        ], $weeks['data']);

        $years = TimeSeriesChart::make('payments_count')->mode(TimeSeriesModes::YEAR)->range(PresetDateRanges::LAST_5_YEARS)->toArray();

        $this->assertCount(5, $years['data']);
        $this->assertEquals(['date' => '2024-01-01', 'payments_count' => 6], end($years['data']));
        $this->assertNull($years['interval']);
    }

    #[Test]
    public function it_applies_the_charts_filters(): void
    {
        [$ahmed] = $this->seedPayments();

        $chart = TimeSeriesChart::make('payments_count')
            ->mode(TimeSeriesModes::DAY)
            ->range(new ExactDateRange('2024-07-03', '2024-07-04 23:59:59'))
            ->filters(['user' => $ahmed->id])
            ->toArray();

        $this->assertEquals([3, 0], array_column($chart['data'], 'payments_count'));
    }

    #[Test]
    public function it_refuses_metrics_that_are_not_registered(): void
    {
        $this->expectException(StatsException::class);

        TimeSeriesChart::make('unknown')->withoutCache()->toArray();
    }

    #[Test]
    public function it_is_json_serializable_with_its_options(): void
    {
        $this->seedPayments();

        $json = json_decode(json_encode(
            TimeSeriesChart::make('payments_count')->title('ފައިސާ')->description('ރުފިޔާ')->valueFormat('integer')->height(200)->id('payments')->brush()
        ), true);

        $this->assertEquals('payments', $json['id']);
        $this->assertEquals('ފައިސާ', $json['title']);
        $this->assertEquals('ރުފިޔާ', $json['description']);
        $this->assertEquals('integer', $json['valueFormat']);
        $this->assertEquals(200, $json['height']);
        $this->assertTrue($json['brush']);
    }
}
