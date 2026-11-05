<?php

namespace Javaabu\Stats\Tests\Feature\Charts;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Stats\Charts\Sparkline;
use Javaabu\Stats\Charts\StatTile;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Facades\Stats;
use Javaabu\Stats\Support\ExactDateRange;
use Javaabu\Stats\Tests\Concerns\InteractsWithStats;
use Javaabu\Stats\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TilesAndSparklinesTest extends TestCase
{
    use InteractsWithStats;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerTestStats();
    }

    #[Test]
    public function it_gives_a_sparkline_one_value_per_step(): void
    {
        $this->seedPayments();

        $sparkline = Sparkline::make('payments_count')
            ->mode(TimeSeriesModes::DAY)
            ->range(new ExactDateRange('2024-07-01', '2024-07-04 23:59:59'))
            ->area()
            ->color('var(--chart-3)')
            ->toArray();

        $this->assertEquals('sparkline', $sparkline['type']);
        $this->assertEquals([0, 0, 3, 2], $sparkline['data']);
        $this->assertEquals('Payments', $sparkline['label']);
        $this->assertEquals('area', $sparkline['variant']);
        $this->assertEquals('var(--chart-3)', $sparkline['color']);
    }

    #[Test]
    public function it_gives_a_tile_the_total_the_previous_total_and_a_sparkline(): void
    {
        $this->seedPayments();

        $tile = StatTile::make('payments_count')
            ->range(new ExactDateRange('2024-07-01', '2024-07-04 23:59:59'))
            ->compare()
            ->sparkline(TimeSeriesModes::DAY)
            ->icon('credit-card')
            ->href('/payments')
            ->label('ފައިސާ')
            ->toArray();

        $this->assertEquals('stat-tile', $tile['type']);
        $this->assertEquals('ފައިސާ', $tile['label']);
        $this->assertEquals(5, $tile['value']);
        $this->assertEquals(1, $tile['previousValue']);
        $this->assertEquals('vs previous period', $tile['deltaContext']);
        $this->assertEquals([0, 0, 3, 2], $tile['sparkline']);
        $this->assertEquals('credit-card', $tile['icon']);
        $this->assertEquals('/payments', $tile['href']);
    }

    #[Test]
    public function it_has_no_delta_or_sparkline_unless_asked(): void
    {
        $this->seedPayments();

        $tile = Stats::tile('payments_sum')->range(new ExactDateRange('2024-07-01', '2024-07-04 23:59:59'))->valueFormat('currency')->toArray();

        $this->assertEquals(400, $tile['value']);
        $this->assertNull($tile['previousValue']);
        $this->assertNull($tile['deltaContext']);
        $this->assertNull($tile['sparkline']);
        $this->assertEquals('currency', $tile['valueFormat']);
    }
}
