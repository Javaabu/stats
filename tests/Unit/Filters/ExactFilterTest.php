<?php

namespace Javaabu\Stats\Tests\Unit\Filters;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Stats\Filters\StatsFilter;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Models\User;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogouts;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogoutsUnregistered;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogoutsWithPermission;
use Javaabu\Stats\TimeSeriesStats;

class ExactFilterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_filter_using_name(): void
    {
        $user_1 = User::factory()->create(['name' => 'User 1']);
        $user_2 = User::factory()->create(['name' => 'User 2']);

        $filter = StatsFilter::exact('name');

        $stat = new UserLogouts();
        $query = User::query();


        $this->assertEquals('User 1', $filter->apply($query, 'User 1', $stat)->first()->name);
    }

    /** @test */
    public function it_can_filter_using_internal_name(): void
    {
        $user_1 = User::factory()->create(['name' => 'User 1']);
        $user_2 = User::factory()->create(['name' => 'User 2']);

        $filter = StatsFilter::exact('username', 'name');

        $stat = new UserLogouts();
        $query = User::query();


        $this->assertEquals('User 2', $filter->apply($query, 'User 2', $stat)->first()->name);
    }
}
