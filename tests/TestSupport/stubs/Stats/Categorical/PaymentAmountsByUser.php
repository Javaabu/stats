<?php

namespace App\Stats\Categorical;

use Javaabu\Stats\Tests\TestSupport\Models\User;
use Javaabu\Stats\Tests\TestSupport\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Repositories\Categorical\SumCategoricalStatsRepository;

class PaymentAmountsByUser extends SumCategoricalStatsRepository
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
        return 'category';
    }

    public function getDateField(): string
    {
        return 'payments.created_at';
    }

    public function getFieldToSum(): string
    {
        // TODO: return the field to sum
    }

    public function getAggregateFieldName(): string
    {
        return 'total';
    }
}
