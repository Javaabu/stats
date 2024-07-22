<?php

namespace Javaabu\Stats\Tests\Feature\Repositories;

use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Models\Payment;
use Javaabu\Stats\Tests\TestSupport\MySQLRefreshDatabase;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\PaymentsSum;

class SumStatsRepositoryTest extends TestCase
{
    use MySQLRefreshDatabase;

    /** @test */
    public function it_can_get_can_get_the_hourly_sum(): void
    {
        $this->travelTo('2024-07-22 8:06 PM');

        Payment::factory()
            ->count(5)
            ->create([
                'paid_at' => '2024-07-22 7:06 PM',
                'amount' => 10,
            ]);

        Payment::factory()
            ->count(2)
            ->create([
                'paid_at' => '2024-07-22 8:06 PM',
                'amount' => 5,
            ]);

        // create the stat
        $stat = new PaymentsSum(PresetDateRanges::TODAY);

        $data = $stat->results(TimeSeriesModes::HOUR)->toArray();

        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        $this->assertEquals([
            [
                'total' => 50,
                'hour' => '2024-07-22 19:00',
            ],
            [
                'total' => 10,
                'hour' => '2024-07-22 20:00',
            ]
        ], $data);
    }

    /** @test */
    public function it_can_get_can_get_the_daily_sum(): void
    {
        $this->travelTo('2024-07-04');

        // yesterday
        Payment::factory()
            ->count(5)
            ->create([
                'paid_at' => '2024-07-03',
                'amount' => 10,
            ]);

        // today
        Payment::factory()
            ->count(2)
            ->create([
                'paid_at' => '2024-07-04',
                'amount' => 5,
            ]);

        // create the stat
        $stat = new PaymentsSum(PresetDateRanges::LAST_7_DAYS);

        $data = $stat->results(TimeSeriesModes::DAY)->toArray();

        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        $this->assertEquals([
            [
                'total' => 50,
                'day' => '2024-07-03',
            ],
            [
                'total' => 10,
                'day' => '2024-07-04',
            ]
        ], $data);
    }

    /** @test */
    public function it_can_get_can_get_the_weekly_sum(): void
    {
        $this->travelTo('2024-07-22 8:06 PM');

        Payment::factory()
            ->count(5)
            ->create([
                'paid_at' => '2024-07-22 7:06 PM',
                'amount' => 10,
            ]);

        Payment::factory()
            ->count(2)
            ->create([
                'paid_at' => '2024-01-03 7:06 PM',
                'amount' => 5,
            ]);

        // create the stat
        $stat = new PaymentsSum(PresetDateRanges::THIS_YEAR);

        $data = $stat->results(TimeSeriesModes::WEEK)->toArray();

        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        $this->assertEquals([
            [
                'total' => 10,
                'week' => '202401',
            ],
            [
                'total' => 50,
                'week' => '202430',
            ]
        ], $data);
    }

    /** @test */
    public function it_can_get_can_get_the_monthly_sum(): void
    {
        $this->travelTo('2024-07-22 8:06 PM');

        Payment::factory()
            ->count(5)
            ->create([
                'paid_at' => '2024-07-22 7:06 PM',
                'amount' => 10,
            ]);

        Payment::factory()
            ->count(2)
            ->create([
                'paid_at' => '2024-01-03 7:06 PM',
                'amount' => 5,
            ]);

        // create the stat
        $stat = new PaymentsSum(PresetDateRanges::THIS_YEAR);

        $data = $stat->results(TimeSeriesModes::MONTH)->toArray();

        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        $this->assertEquals([
            [
                'total' => 10,
                'month' => '2024, 01',
            ],
            [
                'total' => 50,
                'month' => '2024, 07',
            ]
        ], $data);
    }

    /** @test */
    public function it_can_get_can_get_the_yearly_sum(): void
    {
        $this->travelTo('2024-07-22 8:06 PM');

        Payment::factory()
            ->count(5)
            ->create([
                'paid_at' => '2024-07-22 7:06 PM',
                'amount' => 10,
            ]);

        Payment::factory()
            ->count(2)
            ->create([
                'paid_at' => '2023-01-03 7:06 PM',
                'amount' => 5,
            ]);

        // create the stat
        $stat = new PaymentsSum(PresetDateRanges::LIFETIME);

        $data = $stat->results(TimeSeriesModes::YEAR)->toArray();

        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        $this->assertEquals([
            [
                'total' => 10,
                'year' => '2023',
            ],
            [
                'total' => 50,
                'year' => '2024',
            ]
        ], $data);
    }

}
