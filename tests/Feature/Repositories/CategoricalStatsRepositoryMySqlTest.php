<?php

namespace Javaabu\Stats\Tests\Feature\Repositories;

use Javaabu\Stats\Support\ExactDateRange;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Factories\PaymentFactory;
use Javaabu\Stats\Tests\TestSupport\MySQLRefreshDatabase;
use Javaabu\Stats\Tests\TestSupport\Stats\Categorical\PaymentsByUser;

class CategoricalStatsRepositoryMySqlTest extends TestCase
{
    use MySQLRefreshDatabase;

    public function test_it_can_group_and_order_by_a_category_expression(): void
    {
        PaymentFactory::new()->count(2)->create([
            'amount' => 5,
            'paid_at' => '2024-07-03',
        ]);
        PaymentFactory::new()->create([
            'amount' => 15,
            'paid_at' => '2024-07-04',
        ]);

        $stats = new class(new ExactDateRange('2024-07-01', '2024-07-07')) extends PaymentsByUser
        {
            public function getCategoryField(): string
            {
                return "CASE WHEN payments.amount >= 10 THEN 'large' ELSE 'small' END";
            }
        };

        $this->assertSame([
            'large' => 1,
            'small' => 2,
        ], $stats->resultsToArray($stats->results()));
    }
}
