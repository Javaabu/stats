<?php

namespace Javaabu\Stats\Tests\Unit\Filters;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Stats\Filters\StatsFilter;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Models\User;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogoutsRepository;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogoutsUnregistered;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogoutsWithPermission;
use Javaabu\Stats\TimeSeriesStats;

class ScopeFilterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_filter_using_name_scope(): void
    {
        $user_1 = User::factory()->create(['name' => 'John Doe']);
        $user_2 = User::factory()->create(['name' => 'Apple Gate']);

        $filter = StatsFilter::scope('search');

        $stat = new UserLogoutsRepository();
        $query = User::query();


        $this->assertEquals('John Doe', $filter->apply($query, 'Doe', $stat)->first()->name);
    }

    /** @test */
    public function it_can_filter_using_internal_scope_name(): void
    {
        $user_1 = User::factory()->create(['name' => 'John Doe']);
        $user_2 = User::factory()->create(['name' => 'Apple Gate']);

        $filter = StatsFilter::scope('name', 'search');

        $stat = new UserLogoutsRepository();
        $query = User::query();


        $this->assertEquals('Apple Gate', $filter->apply($query, 'Gate', $stat)->first()->name);
    }
}
