<?php

namespace Javaabu\Stats\Contracts;

use Javaabu\Stats\Contracts\Filter as StatsFilter;
use Javaabu\Stencil\Fields\Field;

/**
 * A stats filter people can set in the stats form. Its value is read from
 * its own query parameters (`status=paid`), like javaabu/filters, rather
 * than from `filters[...]`.
 *
 * @mixin StatsFilter
 */
interface HasFields
{
    /**
     * The stencil fields of the filter, named after its query parameters
     *
     * @return array<int, Field>
     */
    public function getFields(): array;

    /**
     * The query parameters the filter reads
     *
     * @return array<int, string>
     */
    public function getQueryKeys(): array;

    /**
     * The filter's value from the input, or null when it doesn't apply
     *
     * @param  array<string, mixed>  $input
     */
    public function resolveValue(array $input): mixed;
}
