<?php

namespace Javaabu\Stats\Repositories\TimeSeries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class SignupsRepository extends CountStatsRepository
{
    /**
     * Get the aggregate field name
     */
    public function getAggregateFieldName(): string
    {
        return 'signups';
    }

    /**
     * Get the main table for the repository
     */
    public function getTable(): string
    {
        $user_class = $this->userModelClass();

        return (new $user_class)->getTable();
    }

    /**
     * Get the base query
     */
    public function query(): Builder
    {
        $user_class = $this->userModelClass();

        return $user_class::query();
    }

    /**
     * Get the use morph class
     */
    public function userMorphClass(): string
    {
        $user_class = $this->userModelClass();

        return (new $user_class)->getMorphClass();
    }


    /**
     * Get the model class
     * @return class-string<Model>
     */
    public abstract function userModelClass(): string;
}
