<?php

namespace Javaabu\Stats\Contracts;

use Javaabu\Stats\Enums\CategoricalModes;

interface CategoricalStatsFormatter
{
    /**
     * Format the data.
     *
     * @param  list<int|string>  $categorical_values
     * @return array<array-key, mixed>
     */
    public function format(
        CategoricalStatsRepository $stats,
        ?CategoricalStatsRepository $compare = null,
        CategoricalModes $mode = CategoricalModes::NON_EMPTY,
        array $categorical_values = []
    ): array;

    /**
     * Get the name of the formatter.
     */
    public function getName(): string;
}
