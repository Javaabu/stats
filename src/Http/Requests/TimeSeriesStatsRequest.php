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
        $user = request()->user();
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
                'nullable',
                'string',
                'date',
                'required_without:date_range',
            ],

            'date_to' => [
                'nullable',
                'string',
                'date',
                'required_without:date_range',
            ],

            'compare_date_range' => [
                'string',
                Rule::enum(PresetDateRanges::class),
            ],

            'compare_date_from' => [
                'nullable',
                'string',
                'date',
                'required_with:compare_date_to'
            ],

            'compare_date_to' => [
                'nullable',
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

    public function bodyParameters()
    {
        return [
            'mode' => [
                'description' => 'Which date frequency to group the data by, like `hour`, `day`, etc.',
                'example' => TimeSeriesModes::MONTH->value,
            ],

            'metric' => [
                'description' => 'Which metric to use for the stat. The available metrics will depend on the user permissions and the filters used.',
                'example' => TimeSeriesStats::allowedMetrics([], request()->user())[0] ?? '',
            ],

            'date_range' => [
                'description' => 'Preset date range to filter results by.',
                'example' => PresetDateRanges::THIS_YEAR->value,
            ],

            'date_from' => [
                'description' => 'Custom starting date when not using a preset date range.',
            ],

            'date_to' => [
                'description' => 'Custom ending date when not using a preset date range.',
            ],

            'compare' => [
                'description' => 'Whether to compare the stat with the comparison date range.',
                'example' => true,
            ],

            'compare_date_range' => [
                'description' => 'Preset date range to compare results with. If not set, the previous date range will be compared.',
                'example' => 'No-example'
            ],

            'compare_date_from' => [
                'description' => 'Custom starting compare date when not using a preset compare date range.',
            ],

            'compare_date_to' => [
                'description' => 'Custom ending compare date when not using a preset compare date range.',
            ],

            'format' => [
                'description' => 'Which format to return the results in.',
            ],

            'filters' => [
                'description' => 'Filters to apply to the stat.',
            ],
        ];
    }
}
