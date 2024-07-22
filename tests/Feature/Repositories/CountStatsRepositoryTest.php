<?php

namespace Javaabu\Stats\Tests\Feature\Repositories;

use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Models\Payment;
use Javaabu\Stats\Tests\TestSupport\MySQLRefreshDatabase;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\PaymentsCount;

class CountStatsRepositoryTest extends TestCase
{
    use MySQLRefreshDatabase;

    /** @test */
    public function it_can_get_can_get_the_hourly_count(): void
    {
        $this->travelTo('2024-07-22 8:06 PM');

        Payment::factory()
            ->count(5)
            ->create([
                'paid_at' => '2024-07-22 7:06 PM',
            ]);

        Payment::factory()
            ->count(2)
            ->create([
                'paid_at' => '2024-07-22 8:06 PM',
            ]);

        // create the stat
        $stat = new PaymentsCount(PresetDateRanges::TODAY);

        $data = $stat->results(TimeSeriesModes::HOUR)->toArray();

        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        $this->assertEquals([
            [
                'count' => 5,
                'hour' => '2024-07-22 19:00',
            ],
            [
                'count' => 2,
                'hour' => '2024-07-22 20:00',
            ]
        ], $data);
    }

    /** @test */
    public function it_can_get_can_get_the_daily_count(): void
    {
        $this->travelTo('2024-07-04');

        // yesterday
        Payment::factory()
            ->count(5)
            ->create([
                'paid_at' => '2024-07-03',
            ]);

        // today
        Payment::factory()
            ->count(2)
            ->create([
                'paid_at' => '2024-07-04',
            ]);

        // create the stat
        $stat = new PaymentsCount(PresetDateRanges::LAST_7_DAYS);

        $data = $stat->results(TimeSeriesModes::DAY)->toArray();

        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        $this->assertEquals([
            [
                'count' => 5,
                'day' => '2024-07-03',
            ],
            [
                'count' => 2,
                'day' => '2024-07-04',
            ]
        ], $data);
    }

    /** @test */
    public function it_can_get_can_get_the_weekly_count(): void
    {
        $this->travelTo('2024-07-22 8:06 PM');

        Payment::factory()
            ->count(5)
            ->create([
                'paid_at' => '2024-07-22 7:06 PM',
            ]);

        Payment::factory()
            ->count(2)
            ->create([
                'paid_at' => '2024-01-03 7:06 PM',
            ]);

        // create the stat
        $stat = new PaymentsCount(PresetDateRanges::THIS_YEAR);

        $data = $stat->results(TimeSeriesModes::WEEK)->toArray();

        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        $this->assertEquals([
            [
                'count' => 2,
                'week' => '202401',
            ],
            [
                'count' => 5,
                'week' => '202430',
            ]
        ], $data);
    }

}
