<?php

namespace Javaabu\Stats\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\TimeSeriesStats;

class TimeSeriesStatsRequest extends FormRequest
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
        $user = $this->user();
        $filters = $this->input('filters', []);

        $rules = [
            'mode' => [
                'required',
                'string',
                Rule::enum(TimeSeriesModes::class),
            ],

            'metric' => [
                'required',
                'string',
                Rule::in(TimeSeriesStats::allowedMetrics($filters, $user)),
            ],

            'date_range' => [
                'string',
                Rule::enum(PresetDateRanges::class),
            ],

            'date_from' => [
                'string',
                'date',
                'required_without:date_range',
            ],

            'date_to' => [
                'string',
                'date',
                'required_without:date_range',
            ],

            'compare_date_range' => [
                'string',
                Rule::enum(PresetDateRanges::class),
            ],

            'compare_date_from' => [
                'string',
                'date',
                'required_with:compare_date_to'
            ],

            'compare_date_to' => [
                'string',
                'date',
                'required_with:compare_date_from',
            ],

            'compare' => [
                'nullable',
                'boolean',
            ],

            'format' => [
                'string',
                Rule::in(TimeSeriesStats::allowedFormats()),
            ],

            'filters' => 'array',
        ];

        return $rules;
    }
}
