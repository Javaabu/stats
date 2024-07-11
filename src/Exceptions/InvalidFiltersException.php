<?php

namespace Javaabu\Stats\Exceptions;

use Illuminate\Support\Collection;

class InvalidFiltersException extends \InvalidArgumentException
{
    public Collection $unknown_filters;

    public Collection $allowed_filters;

    public function __construct(Collection $unknown_filters, Collection $allowed_filters)
    {
        $this->unknown_filters = $unknown_filters;
        $this->allowed_filters = $allowed_filters;

        $unknown_filters = $this->unknown_filters->implode(', ');
        $allowed_filters = $this->allowed_filters->implode(', ');
        $message = "Requested filter(s) `{$unknown_filters}` are not allowed. Allowed filter(s) are `{$allowed_filters}`.";

        parent::__construct($message);
    }

    public static function filtersNotAllowed(Collection $unknown_filters, Collection $allowed_filters)
    {
        return new static(...func_get_args());
    }
}
