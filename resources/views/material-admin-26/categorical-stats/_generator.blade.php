<x-forms::card>
    <x-forms::form id="categorical-stats-form" :action="$url">
        @include('stats::material-admin-26.categorical-stats._form')
    </x-forms::form>
</x-forms::card>

<div class="card">
    <div class="card-body position-relative" style="height: 600px;">
        <canvas id="categorical-chart"></canvas>
    </div>
</div>

@pushonce(config('stats.scripts_stack'))
    @include('stats::material-admin-26.categorical-stats._script')
@endpushonce
