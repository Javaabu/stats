@props(['columns' => 4])

{{-- A responsive grid of stat tiles: one column in narrow containers, up to $columns on wide ones --}}
<div {{ $attributes->merge(['class' => 'stats-tiles']) }} data-slot="stat-tiles" data-columns="{{ $columns }}">
    {{ $slot }}
</div>
