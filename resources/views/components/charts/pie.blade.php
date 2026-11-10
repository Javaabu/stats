<figure {{ $attributes->merge(['class' => 'stats-chart stats-chart--pie', 'id' => $id]) }} data-slot="chart" data-chart-variant="{{ $donut ? 'donut' : 'pie' }}" aria-labelledby="{{ $id }}-title" @if($description) aria-describedby="{{ $id }}-description" @endif>
    @include('stats::components.charts.header')

    @if($has_data)
        <div class="stats-chart__body stats-chart__body--pie" data-slot="chart-body">
            <div class="stats-chart__pie">
                <svg class="stats-chart__svg" viewBox="0 0 {{ $size }} {{ $size }}" role="img" aria-labelledby="{{ $id }}-title" aria-describedby="{{ $id }}-summary" xmlns="http://www.w3.org/2000/svg">
                    @foreach($slices as $slice)
                        @if($slice['path'])
                            <path class="stats-chart__slice" d="{{ $slice['path'] }}" style="--stats-series: {{ $slice['color'] }}"><title>{{ $slice['name'] }}: {{ $slice['value'] }} ({{ $slice['share'] }})</title></path>
                        @endif
                    @endforeach
                </svg>
                @if($center)
                    <div class="stats-chart__center" aria-hidden="true">
                        <span class="stats-chart__center-value">{{ $center['value'] ?? '' }}</span>
                        @if(! empty($center['label']))
                            <span class="stats-chart__center-label">{{ $center['label'] }}</span>
                        @endif
                    </div>
                @endif
            </div>

            <ul class="stats-chart__legend stats-chart__legend--pie" data-slot="chart-legend">
                @foreach($slices as $slice)
                    <li><span class="stats-chart__swatch" style="--stats-series: {{ $slice['color'] }}" aria-hidden="true"></span><span class="stats-chart__legend-name">{{ $slice['name'] }}</span><span class="stats-chart__legend-value" dir="ltr">{{ $slice['share'] }}</span></li>
                @endforeach
            </ul>

            <p id="{{ $id }}-summary" class="stats-sr-only" data-slot="chart-summary">{{ $summary }}</p>
        </div>

        @include('stats::components.charts.table', ['table' => [
            'headers' => [$name_label, $value_label, $share_label],
            'rows' => array_map(fn (array $slice) => ['label' => $slice['name'], 'cells' => [$slice['value'], $slice['share']]], $slices),
        ]])
    @else
        <p class="stats-chart__empty" data-slot="chart-empty">{{ $empty }}</p>
    @endif
</figure>
