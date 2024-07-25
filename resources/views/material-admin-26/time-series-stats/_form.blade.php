@php
    $filters = isset($filters) ? $filters : [];
    $user = isset($user) ? $user : auth()->user();
@endphp

<div class="row">
    <div class="col-md-3">
        <x-forms::select2 name="metric" :options="\Javaabu\Stats\TimeSeriesStats::getMetricNames($filters, $user)" required />

        @if($filters)
            @foreach($filters as $filter => $value)
                <x-forms::hidden :name="'filters[' . $filter . ']'" :value="$value" />
            @endforeach
        @endif
    </div>

    <div class="col-md-3">
        <x-forms::select2 name="mode" :options="\Javaabu\Stats\Enums\TimeSeriesModes::getLabels()" required />
    </div>

    <div class="col-md-3">
        <div data-enable-section-checkbox="#custom-date-range"
             data-disable="true">
            <x-forms::select2
                name="date_range"
                :options="\Javaabu\Stats\Enums\PresetDateRanges::getLabels()"
                :default="\Javaabu\Stats\TimeSeriesStats::defaultDateRange()"
                required />
        </div>
    </div>
    <div class="col-md-3">
        <x-forms::checkbox name="custom_date_range" value="1" id="custom-date-range" />
    </div>
</div>

<div class="row"
     data-enable-section-checkbox="#custom-date-range"
     data-hide-fields="true"
>
    <div class="col-md-6">
        <x-forms::date name="date_from" required />
    </div>
    <div class="col-md-6">
        <x-forms::date name="date_to" required />
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        @php
            $compares = [
                ' ' => __('No Comparision'),
                1 => __('Previous Period'),
                0 => __('Custom Period'),
            ]
        @endphp

        <x-forms::select2 name="compare" :label="__('Compare To')" :options="$compares" />
    </div>
    <div class="col-md-3">
        <div data-enable-elem="#compare"
             data-enable-section-value="{{ json_encode([' ', 1]) }}"
             data-disable="true"
             data-hide-fields="true"
        >
            <x-forms::date name="compare_date_from" required />
        </div>
    </div>
    <div class="col-md-3">
        <div data-enable-elem="#compare"
             data-enable-section-value="{{ json_encode([' ', 1]) }}"
             data-disable="true"
             data-hide-fields="true"
        >
            <x-forms::date name="compare_date_to" required />
        </div>
    </div>
    <div class="col-md-3">
        <div class="button-group inline-btn-group">
            <a href="#" class="btn btn-primary btn--icon-text btn--raised" title="Generate Graph" id="generate-graph">
                <i class="zmdi zmdi-chart"></i> {{ __('Generate') }}
            </a>

            <x-forms::submit color="primary" class="btn--icon-text btn--raised" title="Download CSV">
                <i class="zmdi zmdi-download"></i> {{ __('Download') }}
            </x-forms::submit>
        </div>
    </div>
</div>
