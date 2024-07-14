<?php

namespace Javaabu\Stats\Tests\Unit\Filters;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Stats\Filters\StatsFilter;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Models\User;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogouts;

class ClosureFilterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_filter_using_closure(): void
    {
        $user_1 = User::factory()->create(['name' => 'John Doe']);
        $user_2 = User::factory()->create(['name' => 'Apple Gate']);

        $filter = StatsFilter::closure('name', function ($query, $value, $stat) {
            return $query->where('name', $value);
        });

        $stat = new UserLogouts();
        $query = User::query();


        $this->assertEquals('John Doe', $filter->apply($query, 'John Doe', $stat)->first()->name);
    }
}
