<div {{ $attributes->merge(['class' => 'stats-sparkline']) }} data-slot="sparkline" role="img" aria-label="{{ $label }}" style="--stats-series: {{ $color }}">
    <svg viewBox="0 0 {{ $svg['width'] }} {{ $svg['height'] }}" preserveAspectRatio="none" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
        @if($variant === 'area' && $svg['area'])
            <path class="stats-sparkline__area" d="{{ $svg['area'] }}" />
        @endif
        @if($svg['line'])
            <path class="stats-sparkline__line" d="{{ $svg['line'] }}" vector-effect="non-scaling-stroke" />
        @endif
    </svg>
</div>
