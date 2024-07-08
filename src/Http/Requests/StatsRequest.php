<?php

namespace Javaabu\Stats\Http\Requests;

use Javaabu\Stats\Formatters\StatsFormatter;
use Javaabu\Stats\StatsRepository;
use Illuminate\Foundation\Http\FormRequest;

class StatsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'mode' => 'required|string|in:'.implode(',', array_keys(StatsRepository::getModes())),
            'metric' => 'required|string|in:'.implode(',', array_keys(StatsRepository::METRICS)),
            'date_range' => 'string|in:'.implode(',', array_keys(StatsRepository::getDateRanges())),
            'date_from' => 'string|date|required_without:date_range',
            'date_to' => 'string|date|required_without:date_range',
            'compare_date_range' => 'string|in:'.implode(',', array_keys(StatsRepository::getDateRanges())),
            'compare_date_from' => 'string|date|required_with:compare_date_to',
            'compare_date_to' => 'string|date|required_with:compare_date_from',
            'compare' => 'nullable|boolean',
            'format' => 'string|in:'.implode(',', array_keys(StatsFormatter::getFormatters())),
            'filters' => 'array',
        ];

        return $rules;
    }
}
