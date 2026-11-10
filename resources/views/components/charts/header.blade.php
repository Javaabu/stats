<div class="stats-chart__header" data-slot="chart-header">
    <span id="{{ $id }}-title" class="stats-chart__title" data-slot="chart-title">{{ $title }}</span>
    @if($description)
        <span id="{{ $id }}-description" class="stats-chart__description" data-slot="chart-description">{{ $description }}</span>
    @endif
</div>
