<?php

namespace Javaabu\Stats\Tests\Concerns;

use Illuminate\Support\Facades\Gate;
use Javaabu\Stats\CategoricalStats;
use Javaabu\Stats\Tests\TestSupport\Factories\PaymentFactory;
use Javaabu\Stats\Tests\TestSupport\Models\User;
use Javaabu\Stats\Tests\TestSupport\Stats\Categorical\PaymentAmountsByUser;
use Javaabu\Stats\Tests\TestSupport\Stats\Categorical\PaymentsByUser;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\PaymentsByStatusCount;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\PaymentsCount;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\PaymentsSum;
use Javaabu\Stats\TimeSeriesStats;

trait InteractsWithStats
{
    protected function registerTestStats(): void
    {
        TimeSeriesStats::register([
            'payments_count' => PaymentsCount::class,
            'payments_sum' => PaymentsSum::class,
            'payments_by_status' => PaymentsByStatusCount::class,
        ], false);

        CategoricalStats::register([
            'payments_by_user' => PaymentsByUser::class,
            'payment_amounts_by_user' => PaymentAmountsByUser::class,
        ], false);
    }

    protected function allowViewingStats(): void
    {
        Gate::define('view_stats', fn ($user) => true);
    }

    /**
     * Two users with payments on 3 and 4 July 2024; "today" is 4 July 2024
     *
     * @return array{0: User, 1: User}
     */
    protected function seedPayments(): array
    {
        $this->travelTo('2024-07-04 12:00:00');

        $ahmed = User::factory()->create(['name' => 'އަޙްމަދު']);
        $aisha = User::factory()->create(['name' => 'ޢާއިޝާ']);

        PaymentFactory::new()->withUser($ahmed)->count(3)->create(['paid_at' => '2024-07-03 10:00:00', 'amount' => 100]);
        PaymentFactory::new()->withUser($aisha)->count(2)->create(['paid_at' => '2024-07-04 09:00:00', 'amount' => 50, 'status' => 'pending']);
        PaymentFactory::new()->withUser($aisha)->count(1)->create(['paid_at' => '2024-06-29 09:00:00', 'amount' => 10]);

        return [$ahmed, $aisha];
    }
}
