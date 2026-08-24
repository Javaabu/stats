<?php

namespace App\Stats\Categorical;

use Javaabu\Stats\Tests\TestSupport\Models\User;
use Javaabu\Stats\Tests\TestSupport\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Repositories\Categorical\CountCategoricalStatsRepository;

class PaymentsByUser extends CountCategoricalStatsRepository
{
    public function query(): Builder
    {
        return Payment::query();
    }

    public function categoryProvider(): mixed
    {
        return User::class;
    }

    public function allowedFilters(): array
    {
        return [
            // define your filters here
        ];
    }

    public function getCategoryField(): string
    {
        return 'payments.user_id';
    }

    public function getCategoryFieldAlias(): string
    {
        return 'user';
    }

    public function getDateField(): string
    {
        return 'payments.created_at';
    }

    public function getAggregateFieldName(): string
    {
        return 'count';
    }
}
