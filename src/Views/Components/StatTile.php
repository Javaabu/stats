<?php

namespace Javaabu\Stats\Views\Components;

use Javaabu\Stats\Charts\Chart as StatsChart;

/**
 * A KPI tile:
 *
 * ```blade
 * <x-stats::stat-tiles>
 *     <x-stats::stat-tile :tile="StatTile::make('payments_count')->compare()->sparkline()" />
 * </x-stats::stat-tiles>
 * ```
 */
class StatTile extends Chart
{
    /**
     * @param  StatsChart|array<string, mixed>|null  $tile
     * @param  StatsChart|array<string, mixed>|null  $chart
     */
    public function __construct(StatsChart|array|null $tile = null, StatsChart|array|null $chart = null, ?bool $rtl = null)
    {
        parent::__construct($tile ?? $chart ?? ['type' => 'stat-tile'], $rtl);
    }
}
