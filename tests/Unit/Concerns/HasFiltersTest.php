<?php

namespace Javaabu\Stats\Tests\Unit\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Exceptions\InvalidFiltersException;
use Javaabu\Stats\Filters\StatsFilter;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Factories\ActivityFactory;
use Javaabu\Stats\Tests\TestSupport\Models\User;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogoutsRepository;

class HasFiltersTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_get_filters(): void
    {
        $stat = new UserLogoutsRepository(PresetDateRanges::LAST_7_DAYS, ['user' => 1]);

        $this->assertEquals(['user' => 1], $stat->getFilters());
    }

    /** @test */
    public function it_can_get_single_filter_value(): void
    {
        $stat = new UserLogoutsRepository(PresetDateRanges::LAST_7_DAYS, ['user' => 1]);

        $this->assertEquals(1, $stat->getFilter('user'));
    }

    /** @test */
    public function it_can_get_non_existing_filter_value(): void
    {
        $stat = new UserLogoutsRepository(PresetDateRanges::LAST_7_DAYS);

        $this->assertNull($stat->getFilter('user'));
    }

    /** @test */
    public function it_can_get_default_value_for_non_existing_filter_value(): void
    {
        $stat = new UserLogoutsRepository(PresetDateRanges::LAST_7_DAYS);

        $this->assertEquals(2, $stat->getFilter('user', 2));
    }

    /** @test */
    public function it_can_get_filtered_query(): void
    {
        $user_1 = User::factory()->create();

        ActivityFactory::new()
            ->logout()
            ->withUser($user_1)
            ->create([
                'created_at' => '2024-07-04 00:00:00',
            ]);

        $user_2 = User::factory()->create();

        ActivityFactory::new()
            ->logout()
            ->withUser($user_2)
            ->create([
                'created_at' => '2024-07-07 00:00:00',
            ]);

        $stat = new UserLogoutsRepository(PresetDateRanges::LIFETIME, ['user' => 2]);

        $first_log = $stat->filteredQuery()->first();

        $this->assertEquals('2024-07-07 00:00:00', $first_log->created_at->toDateTimeString());
    }

    /** @test */
    public function it_can_set_filters(): void
    {
        $stat = new UserLogoutsRepository(PresetDateRanges::LAST_7_DAYS);

        $this->assertNull($stat->getFilter('user'));

        $stat->setFilters(['user' => 1]);

        $this->assertEquals(1, $stat->getFilter('user'));
    }

    /** @test */
    public function it_does_not_allow_setting_disallowed_filters(): void
    {
        $this->expectException(InvalidFiltersException::class);

        $stat = new UserLogoutsRepository(PresetDateRanges::LAST_7_DAYS, ['user' => 1, 'admin' => 2]);
    }

    /** @test */
    public function it_can_check_allowed_filters_for_associative_arrays(): void
    {
        $stat = new UserLogoutsRepository(PresetDateRanges::LAST_7_DAYS);

        $this->assertTrue($stat->ensureAllFiltersAllowed(['user' => 1]));
        $this->assertFalse($stat->ensureAllFiltersAllowed(['user' => 1, 'admin' => 2]));
    }

    /** @test */
    public function it_can_check_allowed_filters_for_list_arrays(): void
    {
        $stat = new UserLogoutsRepository(PresetDateRanges::LAST_7_DAYS);

        $this->assertTrue($stat->ensureAllFiltersAllowed(['user']));
        $this->assertFalse($stat->ensureAllFiltersAllowed(['user', 'admin']));
    }

    /** @test */
    public function it_can_get_the_allowed_filters(): void
    {
        $stat = new UserLogoutsRepository(PresetDateRanges::LAST_7_DAYS);

        $this->assertEquals([StatsFilter::exact('user', 'causer_id')], $stat->allowedFilters());
    }

    /** @test */
    public function it_can_apply_filters(): void
    {
        $user_1 = User::factory()->create();

        ActivityFactory::new()
            ->logout()
            ->withUser($user_1)
            ->create([
                'created_at' => '2024-07-04 00:00:00',
            ]);

        $user_2 = User::factory()->create();

        ActivityFactory::new()
            ->logout()
            ->withUser($user_2)
            ->create([
                'created_at' => '2024-07-07 00:00:00',
            ]);

        $stat = new UserLogoutsRepository(PresetDateRanges::LIFETIME, ['user' => 1]);

        $first_log = $stat->applyFilters($stat->query())->first();

        $this->assertEquals('2024-07-04 00:00:00', $first_log->created_at->toDateTimeString());
    }

    /** @test */
    public function it_can_check_for_allowed_filters(): void
    {
        $stat = new UserLogoutsRepository(PresetDateRanges::LAST_7_DAYS);

        $this->assertTrue($stat->isAllowedFilter('user'));
        $this->assertFalse($stat->isAllowedFilter('admin'));
    }


}
