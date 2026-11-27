@props(['value', 'label' => false])
@php
    $lvl = \App\Services\SurveyReport::level($value);
    $num = $value === null ? '—' : number_format(round($value, 1), 1, ',', '');
@endphp
@if ($label)
    <span {{ $attributes->merge(['class' => "inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold {$lvl['pill']}"]) }}>
        <span class="size-2 rounded-full {{ $lvl['dot'] }}"></span>
        {{ $lvl['label'] }}
        @if ($value !== null) <span class="font-normal opacity-70 tabular-nums">{{ $num }}</span> @endif
    </span>
@else
    <span title="{{ $lvl['label'] }}" {{ $attributes->merge(['class' => "inline-block min-w-11 rounded-md px-2 py-0.5 text-center text-sm font-semibold tabular-nums {$lvl['pill']}"]) }}>{{ $num }}</span>
@endif
