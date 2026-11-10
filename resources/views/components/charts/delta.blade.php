{{-- A change: arrow, sign and colour, read out as "Up 12%" --}}
<span class="stats-delta" data-slot="delta" data-direction="{{ $delta['direction'] }}" data-sentiment="{{ $delta['sentiment'] }}">
    @if($delta['direction'] !== 'flat')
        <x-forms::icon :name="$delta['direction'] === 'up' ? 'arrow-up' : 'arrow-down'" class="stats-delta__icon" />
    @endif
    <span class="stats-delta__value" dir="ltr" aria-hidden="true">{{ $delta['text'] }}</span>
    <span class="stats-sr-only">{{ $delta['spoken'] }}</span>
    @if(! empty($context))
        <span class="stats-delta__context">{{ $context }}</span>
    @endif
</span>
