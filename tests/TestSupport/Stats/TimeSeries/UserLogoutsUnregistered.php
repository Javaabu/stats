<?php
namespace Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries;

use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Repositories\TimeSeries\CountStatsRepository;

class UserLogoutsUnregistered extends CountStatsRepository
{

    /**
     * @var string
     */
    protected string $table = 'activity_log';

    /**
     * @var string
     */
    protected string $aggregate_field = 'logins';

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
     */
    public function query(): Builder
    {
        return $this->baseQuery()->whereCauserType('user');
    }
}
