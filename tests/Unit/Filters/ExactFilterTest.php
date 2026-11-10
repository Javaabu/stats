<?php

namespace Javaabu\Stats\Tests\Unit\Filters;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Stats\Filters\StatsFilter;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Models\User;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogoutsRepository;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;

/** Needs spatie/laravel-activitylog, which is optional */
#[RequiresMethod(Activity::class, 'causer')]
class ExactFilterTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_filter_using_name(): void
    {
        $user_1 = User::factory()->create(['name' => 'User 1']);
        $user_2 = User::factory()->create(['name' => 'User 2']);

        $filter = StatsFilter::exact('name');

        $stat = new UserLogoutsRepository;
        $query = User::query();

        $this->assertEquals('User 1', $filter->apply($query, 'User 1', $stat)->first()->name);
    }

    #[Test]
    public function it_can_filter_using_internal_name(): void
    {
        $user_1 = User::factory()->create(['name' => 'User 1']);
        $user_2 = User::factory()->create(['name' => 'User 2']);

        $filter = StatsFilter::exact('username', 'name');

        $stat = new UserLogoutsRepository;
        $query = User::query();

        $this->assertEquals('User 2', $filter->apply($query, 'User 2', $stat)->first()->name);
    }
}
