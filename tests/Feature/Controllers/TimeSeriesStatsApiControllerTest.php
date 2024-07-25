<?php

namespace Javaabu\Stats\Tests\Feature\Controllers;

use Illuminate\Support\Facades\Gate;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Factories\ActivityFactory;
use Javaabu\Stats\Tests\TestSupport\Models\User;
use Javaabu\Stats\Tests\TestSupport\MySQLRefreshDatabase;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\TestUserLoginsRepository;
use Javaabu\Stats\TimeSeriesStats;

class TimeSeriesStatsApiControllerTest extends TestCase
{
    use MySQLRefreshDatabase;

    public function setUp(): void
    {
        $this->setupMySql(true);

        parent::setUp();

        TimeSeriesStats::registerApiRoute();
    }

    /** @test */
    public function it_can_generate_time_series_stats_with_filters(): void
    {
        $this->travelTo('2024-07-04');

        TimeSeriesStats::register([
            'user_logins' => TestUserLoginsRepository::class
        ], false);

        Gate::define('view_stats', function (User $user) {
            return true;
        });

        // yesterday
        ActivityFactory::new()
            ->login()
            ->withUser()
            ->count(5)
            ->create([
                'created_at' => '2024-07-03',
            ]);

        $user = User::factory()->create();

        // today
        ActivityFactory::new()
            ->login()
            ->withUser($user)
            ->count(2)
            ->create([
                'created_at' => '2024-07-04',
            ]);

        $this->actingAs($user);

        $this->get(add_query_arg(
            [
                'metric' => 'user_logins',
                'format' => 'chartjs',
                'date_range' => 'last_7_days',
                'mode' => 'day',
                'filters' => [
                    'user' => $user->id,
                ]
            ],
            '/stats/time-series'
        ))
            ->assertSuccessful()
            ->assertJsonFragment([
                'metric' => 'user_logins',
                'metric_name' => 'Test User Logins',
                'mode' => 'day',
                'aggregate_field' => 'logins',
                'aggregate_field_label' => 'Logins',
                'date_range' => 'last_7_days',
                'date_from' => '2024-06-28 00:00:00',
                'date_to' => '2024-07-04 23:59:59',
                'compare_date_range' => null,
                'compare_date_from' => null,
                'compare_date_to' => null,
                'format' => 'chartjs',
            ])
            ->assertJsonFragment([
                'filters' => [
                    'user' => (string) $user->id,
                ]
            ])
            ->assertJsonFragment([
                'result' => [
                    'labels' => [
                        '28 Jun 24',
                        '29 Jun 24',
                        '30 Jun 24',
                        '1 Jul 24',
                        '2 Jul 24',
                        '3 Jul 24',
                        '4 Jul 24',
                    ],

                    'stats' => [
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        2,
                    ],

                    'compare' => null
                ]
            ]);
    }

    /** @test */
    public function it_can_generate_time_series_stats_from_api_with_preset_date_range(): void
    {
        $this->travelTo('2024-07-04');

        TimeSeriesStats::register([
            'user_logins' => TestUserLoginsRepository::class
        ], false);

        Gate::define('view_stats', function (User $user) {
            return true;
        });

        // yesterday
        ActivityFactory::new()
            ->login()
            ->withUser()
            ->count(5)
            ->create([
                'created_at' => '2024-07-03',
            ]);

        // today
        ActivityFactory::new()
            ->login()
            ->withUser()
            ->count(2)
            ->create([
                'created_at' => '2024-07-04',
            ]);

        $user = User::factory()->create();

        $this->actingAs($user);

        $this->get(add_query_arg(
                [
                    'metric' => 'user_logins',
                    'format' => 'chartjs',
                    'date_range' => 'last_7_days',
                    'mode' => 'day',
                    'compare' => true
                ],
                '/stats/time-series'
        ))
            ->assertSuccessful()
            ->assertJsonFragment([
                'metric' => 'user_logins',
                'metric_name' => 'Test User Logins',
                'mode' => 'day',
                'aggregate_field' => 'logins',
                'aggregate_field_label' => 'Logins',
                'date_range' => 'last_7_days',
                'date_from' => '2024-06-28 00:00:00',
                'date_to' => '2024-07-04 23:59:59',
                'compare_date_range' => 'previous',
                'compare_date_from' => '2024-06-21 00:00:00',
                'compare_date_to' => '2024-06-27 23:59:59',
                'format' => 'chartjs',
            ])
            ->assertJsonFragment([
                'filters' => []
            ])
            ->assertJsonFragment([
                'result' => [
                    'labels' => [
                        '28 Jun 24#21 Jun 24',
                        '29 Jun 24#22 Jun 24',
                        '30 Jun 24#23 Jun 24',
                        '1 Jul 24#24 Jun 24',
                        '2 Jul 24#25 Jun 24',
                        '3 Jul 24#26 Jun 24',
                        '4 Jul 24#27 Jun 24',
                    ],

                    'stats' => [
                        0,
                        0,
                        0,
                        0,
                        0,
                        5,
                        2,
                    ],

                    'compare' => [
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_generate_time_series_stats_from_api_with_custom_date_range(): void
    {
        $this->travelTo('2024-07-04');

        TimeSeriesStats::register([
            'user_logins' => TestUserLoginsRepository::class
        ], false);

        Gate::define('view_stats', function (User $user) {
            return true;
        });

        // yesterday
        ActivityFactory::new()
            ->login()
            ->withUser()
            ->count(5)
            ->create([
                'created_at' => '2024-07-03',
            ]);

        // today
        ActivityFactory::new()
            ->login()
            ->withUser()
            ->count(2)
            ->create([
                'created_at' => '2024-07-04',
            ]);

        $user = User::factory()->create();

        $this->actingAs($user);

        $this->get(add_query_arg(
            [
                'metric' => 'user_logins',
                'format' => 'chartjs',
                'date_from' => '2024-07-02',
                'date_to' => '2024-07-07',
                'mode' => 'day',
                'compare' => true
            ],
            '/stats/time-series'
        ))
            ->assertSuccessful()
            ->assertJsonFragment([
                'metric' => 'user_logins',
                'metric_name' => 'Test User Logins',
                'mode' => 'day',
                'aggregate_field' => 'logins',
                'aggregate_field_label' => 'Logins',
                'date_range' => 'custom',
                'date_from' => '2024-07-02 00:00:00',
                'date_to' => '2024-07-07 00:00:00',
                'compare_date_range' => 'previous',
                'compare_date_from' => '2024-06-26 23:59:59',
                'compare_date_to' => '2024-07-01 23:59:59',
                'format' => 'chartjs',
            ])
            ->assertJsonFragment([
                'filters' => []
            ])
            ->assertJsonFragment([
                'result' => [
                    'labels' => [
                        '2 Jul 24#26 Jun 24',
                        '3 Jul 24#27 Jun 24',
                        '4 Jul 24#28 Jun 24',
                        '5 Jul 24#29 Jun 24',
                        '6 Jul 24#30 Jun 24',
                        '7 Jul 24#1 Jul 24',
                    ],

                    'stats' => [
                        0,
                        5,
                        2,
                        0,
                        0,
                        0,
                    ],

                    'compare' => [
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_generate_time_series_stats_from_api_with_preset_previous_date_range(): void
    {
        $this->travelTo('2024-07-04');

        TimeSeriesStats::register([
            'user_logins' => TestUserLoginsRepository::class
        ], false);

        Gate::define('view_stats', function (User $user) {
            return true;
        });

        // last week
        ActivityFactory::new()
            ->login()
            ->withUser()
            ->count(10)
            ->create([
                'created_at' => '2024-06-25',
            ]);

        // yesterday
        ActivityFactory::new()
            ->login()
            ->withUser()
            ->count(5)
            ->create([
                'created_at' => '2024-07-03',
            ]);

        // today
        ActivityFactory::new()
            ->login()
            ->withUser()
            ->count(2)
            ->create([
                'created_at' => '2024-07-04',
            ]);

        $user = User::factory()->create();

        $this->actingAs($user);

        $this->get(add_query_arg(
            [
                'metric' => 'user_logins',
                'format' => 'chartjs',
                'date_range' => 'last_7_days',
                'mode' => 'day',
                'compare_date_range' => 'last_week'
            ],
            '/stats/time-series'
        ))
            ->assertSuccessful()
            ->assertJsonFragment([
                'metric' => 'user_logins',
                'metric_name' => 'Test User Logins',
                'mode' => 'day',
                'aggregate_field' => 'logins',
                'aggregate_field_label' => 'Logins',
                'date_range' => 'last_7_days',
                'date_from' => '2024-06-28 00:00:00',
                'date_to' => '2024-07-04 23:59:59',
                'compare_date_range' => 'last_week',
                'compare_date_from' => '2024-06-23 00:00:00',
                'compare_date_to' => '2024-06-29 23:59:59',
                'format' => 'chartjs',
            ])
            ->assertJsonFragment([
                'filters' => []
            ])
            ->assertJsonFragment([
                'result' => [
                    'labels' => [
                        '28 Jun 24#23 Jun 24',
                        '29 Jun 24#24 Jun 24',
                        '30 Jun 24#25 Jun 24',
                        '1 Jul 24#26 Jun 24',
                        '2 Jul 24#27 Jun 24',
                        '3 Jul 24#28 Jun 24',
                        '4 Jul 24#29 Jun 24',
                    ],

                    'stats' => [
                        0,
                        0,
                        0,
                        0,
                        0,
                        5,
                        2,
                    ],

                    'compare' => [
                        0,
                        0,
                        10,
                        0,
                        0,
                        0,
                        0,
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_generate_time_series_stats_from_api_with_custom_previous_date_range(): void
    {
        $this->travelTo('2024-07-04');

        TimeSeriesStats::register([
            'user_logins' => TestUserLoginsRepository::class
        ], false);

        Gate::define('view_stats', function (User $user) {
            return true;
        });

        // last week
        ActivityFactory::new()
            ->login()
            ->withUser()
            ->count(10)
            ->create([
                'created_at' => '2024-06-25',
            ]);

        // yesterday
        ActivityFactory::new()
            ->login()
            ->withUser()
            ->count(5)
            ->create([
                'created_at' => '2024-07-03',
            ]);

        // today
        ActivityFactory::new()
            ->login()
            ->withUser()
            ->count(2)
            ->create([
                'created_at' => '2024-07-04',
            ]);

        $user = User::factory()->create();

        $this->actingAs($user);

        $this->get(add_query_arg(
            [
                'metric' => 'user_logins',
                'format' => 'chartjs',
                'date_range' => 'last_7_days',
                'mode' => 'day',
                'compare_date_from' => '2024-06-23',
                'compare_date_to' => '2024-06-26'
            ],
            '/stats/time-series'
        ))
            ->assertSuccessful()
            ->assertJsonFragment([
                'metric' => 'user_logins',
                'metric_name' => 'Test User Logins',
                'mode' => 'day',
                'aggregate_field' => 'logins',
                'aggregate_field_label' => 'Logins',
                'date_range' => 'last_7_days',
                'date_from' => '2024-06-28 00:00:00',
                'date_to' => '2024-07-04 23:59:59',
                'compare_date_range' => 'custom',
                'compare_date_from' => '2024-06-23 00:00:00',
                'compare_date_to' => '2024-06-26 00:00:00',
                'format' => 'chartjs',
            ])
            ->assertJsonFragment([
                'filters' => []
            ])
            ->assertJsonFragment([
                'result' => [
                    'labels' => [
                        '28 Jun 24#23 Jun 24',
                        '29 Jun 24#24 Jun 24',
                        '30 Jun 24#25 Jun 24',
                        '1 Jul 24#26 Jun 24',
                        '2 Jul 24#27 Jun 24',
                        '3 Jul 24#28 Jun 24',
                        '4 Jul 24#29 Jun 24',
                    ],

                    'stats' => [
                        0,
                        0,
                        0,
                        0,
                        0,
                        5,
                        2,
                    ],

                    'compare' => [
                        0,
                        0,
                        10,
                        0,
                        0,
                        0,
                        0,
                    ]
                ]
            ]);
    }
}
