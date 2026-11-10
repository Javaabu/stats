@php($tag = $href ? 'a' : 'div')
<{{ $tag }} {{ $attributes->merge(['class' => 'stats-tile', 'id' => $id]) }} data-slot="stat-tile" @if($href) href="{{ $href }}" @else role="group" @endif aria-labelledby="{{ $id }}-label">
    <div class="stats-tile__header">
        <span id="{{ $id }}-label" class="stats-tile__label" data-slot="stat-tile-label">{{ $label }}</span>
        @if($icon)
            <x-forms::icon :name="$icon" class="stats-tile__icon" />
        @endif
    </div>

    <div class="stats-tile__value" data-slot="stat-tile-value" dir="auto">{{ $value }}</div>

    @if($delta)
        @include('stats::components.charts.delta', ['delta' => $delta, 'context' => $delta_context])
    @elseif($period)
        <span class="stats-tile__period">{{ $period }}</span>
    @endif

    @if($sparkline)
        <div class="stats-sparkline stats-tile__sparkline" data-slot="sparkline" role="img" aria-label="{{ $sparkline['label'] }}">
            <svg viewBox="0 0 {{ $sparkline['svg']['width'] }} {{ $sparkline['svg']['height'] }}" preserveAspectRatio="none" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
                @if($sparkline['svg']['area'])
                    <path class="stats-sparkline__area" d="{{ $sparkline['svg']['area'] }}" />
                @endif
                @if($sparkline['svg']['line'])
                    <path class="stats-sparkline__line" d="{{ $sparkline['svg']['line'] }}" vector-effect="non-scaling-stroke" />
                @endif
            </svg>
        </div>
    @endif

    @if($footer)
        <div class="stats-tile__footer">{{ $footer }}</div>
    @endif
</{{ $tag }}>
