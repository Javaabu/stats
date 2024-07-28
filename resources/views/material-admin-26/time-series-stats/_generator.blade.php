<x-forms::card>
    <x-forms::form id="stats-form" :action="$url">
        @include('stats::material-admin-26.time-series-stats._form')
    </x-forms::form>
</x-forms::card>

<div class="card">
    <div class="card-body position-relative" style="height: 500px;">
        <canvas id="chart"></canvas>
    </div>
</div>

@pushonce(config('stats.scripts_stack'))
    @include('stats::material-admin-26.time-series-stats._script')
@endpushonce
