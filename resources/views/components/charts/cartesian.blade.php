<figure {{ $attributes->merge(['class' => 'stats-chart', 'id' => $id]) }} data-slot="chart" data-chart-variant="{{ $svg['variant'] }}" aria-labelledby="{{ $id }}-title" @if($description) aria-describedby="{{ $id }}-description" @endif>
    @include('stats::components.charts.header')

    @if($totals)
        <div class="stats-chart__totals" data-slot="period-totals">
            <div class="stats-chart__total">
                <span class="stats-chart__total-label">{{ $totals['current_label'] }}</span>
                <span class="stats-chart__total-value" data-slot="period-current-total">{{ $totals['current'] }}</span>
            </div>
            <div class="stats-chart__total stats-chart__total--previous">
                <span class="stats-chart__total-label">{{ $totals['previous_label'] }}</span>
                <span class="stats-chart__total-value" data-slot="period-previous-total">{{ $totals['previous'] }}</span>
            </div>
            @if($totals['delta'])
                @include('stats::components.charts.delta', ['delta' => $totals['delta'], 'context' => null])
            @endif
        </div>
    @endif

    @if($has_data)
        <div class="stats-chart__body" data-slot="chart-body">
            <svg class="stats-chart__svg" viewBox="0 0 {{ $svg['width'] }} {{ $svg['height'] }}" role="img" aria-labelledby="{{ $id }}-title" aria-describedby="{{ $id }}-summary" xmlns="http://www.w3.org/2000/svg">
                <g class="stats-chart__grid" aria-hidden="true">
                    @foreach($svg['grid'] as $line)
                        <line x1="{{ $line['x1'] }}" x2="{{ $line['x2'] }}" y1="{{ $line['y1'] }}" y2="{{ $line['y2'] }}" />
                        <text x="{{ $line['label_x'] }}" y="{{ $line['label_y'] }}" text-anchor="{{ $line['anchor'] }}" class="stats-chart__tick">{{ $line['label'] }}</text>
                    @endforeach
                    @foreach($svg['axis_labels'] as $label)
                        <text x="{{ $label['x'] }}" y="{{ $label['y'] }}" text-anchor="{{ $label['anchor'] }}" class="stats-chart__tick">{{ $label['label'] }}</text>
                    @endforeach
                    @if($svg['zero'])
                        <line class="stats-chart__zero" x1="{{ $svg['zero']['x1'] }}" x2="{{ $svg['zero']['x2'] }}" y1="{{ $svg['zero']['y1'] }}" y2="{{ $svg['zero']['y2'] }}" />
                    @endif
                </g>

                @foreach($svg['series'] as $series)
                    <g class="stats-chart__series" data-key="{{ $series['key'] }}" @if($series['previous']) data-previous @endif style="--stats-series: {{ $series['color'] }}">
                        @if($series['area'])
                            <path class="stats-chart__area" d="{{ $series['area'] }}" />
                        @endif
                        @if($series['line'])
                            <path class="stats-chart__line" d="{{ $series['line'] }}" @if($series['dashed']) stroke-dasharray="5 4" @endif />
                        @endif
                        @foreach($series['bars'] as $bar)
                            <rect class="stats-chart__bar" x="{{ $bar['x'] }}" y="{{ $bar['y'] }}" width="{{ $bar['width'] }}" height="{{ $bar['height'] }}" rx="2"><title>{{ $bar['title'] }}</title></rect>
                        @endforeach
                        @foreach($series['points'] as $point)
                            <circle class="stats-chart__point" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="{{ count($series['points']) <= 31 ? 3 : 6 }}" @if(count($series['points']) > 31) data-hidden @endif><title>{{ $point['title'] }}</title></circle>
                        @endforeach
                    </g>
                @endforeach
            </svg>

            @if($legend)
                <ul class="stats-chart__legend" data-slot="chart-legend">
                    @foreach($legend as $item)
                        <li><span class="stats-chart__swatch" style="--stats-series: {{ $item['color'] }}" @if($item['dashed']) data-dashed @endif aria-hidden="true"></span>{{ $item['label'] }}</li>
                    @endforeach
                </ul>
            @endif

            <p id="{{ $id }}-summary" class="stats-sr-only" data-slot="chart-summary">{{ $summary }}</p>
        </div>

        @include('stats::components.charts.table', ['table' => $table])
    @else
        <p class="stats-chart__empty" data-slot="chart-empty">{{ $empty }}</p>
    @endif
</figure>
