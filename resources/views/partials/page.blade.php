{{-- The stats page body: the form, then the chart and the CSV download --}}
<div class="stats-page" data-stats-page="{{ $page }}">
    <h1 class="stats-page__title">{{ $strings['title'] }}</h1>

    {{ $form }}

    @if($chart)
        <x-stats::chart :chart="$chart" />

        @if($export)
            <form method="POST" action="{{ $export['url'] }}" class="stats-page__export">
                @csrf
                @foreach(\Illuminate\Support\Arr::dot(array_filter($export['params'], fn ($value) => $value !== null && $value !== '' && $value !== [])) as $name => $value)
                    @php
                        $segments = explode('.', $name);
                        $input_name = array_shift($segments) . implode('', array_map(fn ($segment) => is_numeric($segment) ? '[]' : "[{$segment}]", $segments));
                    @endphp
                    <input type="hidden" name="{{ $input_name }}" value="{{ $value }}">
                @endforeach
                <x-forms::submit color="secondary">{{ $strings['export'] }}</x-forms::submit>
            </form>
        @endif
    @elseif(isset($errors) && $errors->any())
        <x-forms::alert type="warning">{{ $strings['invalid'] }}</x-forms::alert>
    @else
        <p class="stats-page__empty">{{ $strings['empty'] }}</p>
    @endif
</div>
