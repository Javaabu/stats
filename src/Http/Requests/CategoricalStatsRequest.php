<?php

namespace Javaabu\Stats\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Javaabu\Stats\CategoricalStats;
use Javaabu\Stats\Enums\CategoricalModes;
use Javaabu\Stats\Enums\PresetDateRanges;

class CategoricalStatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $filters = $this->input('filters', []);
        $user = $this->user();

        return [
            'metric' => [
                'required',
                'string',
                Rule::in(CategoricalStats::allowedMetrics($filters, $user)),
            ],
            'mode' => [
                'required',
                'string',
                Rule::enum(CategoricalModes::class),
            ],
            'categorical_values' => [
                'required_if:mode,'.CategoricalModes::SPECIFIC->value,
                'array',
                'min:1',
            ],
            'categorical_values.*' => [
                'required',
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
                'required_with:compare_date_to',
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
                Rule::in(CategoricalStats::allowedFormats()),
            ],
            'filters' => [
                'array',
            ],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public function bodyParameters(): array
    {
        return [
            'metric' => [
                'description' => 'The registered metric. Availability depends on user permissions and requested filters.',
                'example' => CategoricalStats::allowedMetrics([], $this->user())[0] ?? '',
            ],
            'mode' => [
                'description' => 'Categories to include: non_empty, all, or specific.',
                'example' => CategoricalModes::NON_EMPTY->value,
            ],
            'categorical_values.*' => [
                'description' => 'Category ids. Required when mode is specific.',
            ],
            'date_range' => [
                'description' => 'Preset date range used to filter results.',
                'example' => PresetDateRanges::THIS_YEAR->value,
            ],
            'date_from' => [
                'description' => 'Custom starting date when not using a preset date range.',
            ],
            'date_to' => [
                'description' => 'Custom ending date when not using a preset date range.',
            ],
            'compare' => [
                'description' => 'Whether to compare with the previous period.',
                'example' => true,
            ],
            'compare_date_range' => [
                'description' => 'Preset comparison date range.',
            ],
            'compare_date_from' => [
                'description' => 'Custom comparison starting date.',
            ],
            'compare_date_to' => [
                'description' => 'Custom comparison ending date.',
            ],
            'format' => [
                'description' => 'Registered output formatter.',
                'example' => 'chartjs',
            ],
            'filters' => [
                'description' => 'Model-specific filters to apply to the stat.',
            ],
        ];
    }
}
