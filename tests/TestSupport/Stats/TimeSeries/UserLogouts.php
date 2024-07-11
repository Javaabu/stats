<?php
namespace Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries;

use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Repositories\TimeSeries\CountStatsRepository;

class UserLogouts extends CountStatsRepository
{

    /**
     * @var string
     */
    protected $table = 'activity_log';

    /**
     * @var string
     */
    protected $aggregate_field = 'logins';

    /**
     * Get the base query
     */
    public function baseQuery(): Builder
    {
        $model = config('activitylog.activity_model');

        return $model::query()->whereDescription('logout');
    }

    /**
     * Get the base query
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query(): Builder
    {
        return $this->baseQuery()->whereCauserType('user');
    }
}
