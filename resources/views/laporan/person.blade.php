@extends('layouts.app')
@section('title', $p['code'])
@section('home', route('laporan.summary'))
@section('nav') @include('laporan._nav') @endsection

@section('content')
@php use App\Services\SurveyReport; @endphp

<a href="{{ route('laporan.people') }}" class="text-sm text-teal-700 hover:underline print:hidden">&larr; Semua karyawan</a>

<div class="mt-3 flex flex-wrap items-start justify-between gap-4 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
    <div>
        <p class="font-mono text-sm font-semibold text-teal-700">{{ $p['code'] }}</p>
        <h1 class="text-2xl font-bold text-slate-900">{{ $p['name'] ?? 'Anonim' }}</h1>
        <p class="text-slate-600">{{ $p['division'] }}</p>
        @if ($p['contact'] !== null)
            <p class="mt-2 text-sm text-slate-600">Bersedia dihubungi{{ $p['contact'] ? ': '.$p['contact'] : '' }}</p>
        @endif
    </div>
    <div class="sm:text-right">
        <p class="text-sm text-slate-500">Kondisi keseluruhan</p>
        <x-score :value="$p['avg']" :label="true" class="mt-1" />
    </div>
</div>

@php
    $weak = collect(config('survei.days'))->filter(fn ($day, $d) => SurveyReport::level($p['days'][$d]['score'] ?? null)['key'] === 'bad')->pluck('short');
@endphp
@if (count($p['flags']) || $weak->isNotEmpty())
    <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-900">
        @if ($weak->isNotEmpty())
            <p><strong>Perlu perhatian di:</strong> {{ $weak->join(', ', ' dan ') }}.</p>
        @endif
        @foreach ($p['flags'] as $f)
            <p class="mt-1">• {{ $f['text'] }} <strong>{{ $f['answer'] }}</strong></p>
        @endforeach
    </div>
@endif

<div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
    @foreach (config('survei.days') as $d => $day)
        <a href="#hari-{{ $d }}" class="rounded-xl border border-stone-200 bg-white p-3 text-center shadow-sm hover:border-teal-400">
            <p class="text-xs text-slate-500">Hari {{ $d }}</p>
            <p class="text-sm font-semibold text-slate-800">{{ $day['short'] }}</p>
            <x-score :value="$p['days'][$d]['score'] ?? null" class="mt-1.5" />
        </a>
    @endforeach
</div>

<div class="mt-2">@include('laporan._legend')</div>

@foreach (config('survei.days') as $d => $day)
    <section id="hari-{{ $d }}" class="mt-6 break-inside-avoid rounded-2xl border border-stone-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-stone-200 px-5 py-3">
            <h2 class="font-semibold text-slate-900">Hari {{ $d }}: {{ $day['title'] }}</h2>
            @isset($p['days'][$d])
                <span class="text-xs text-slate-500">Diisi {{ $p['days'][$d]['submitted_at']->translatedFormat('d M Y, H:i') }}</span>
            @endisset
        </div>

        @if (! isset($p['days'][$d]))
            <p class="px-5 py-4 text-sm text-slate-400">Belum mengisi.</p>
        @else
            @php $answers = $p['days'][$d]['answers']; @endphp
            <ol class="divide-y divide-stone-100">
                @foreach ($day['questions'] as $i => $q)
                    @php
                        $v = $answers[$q['id']] ?? null;
                        $flag = SurveyReport::isFlagged($q, $v);
                        $score = SurveyReport::scoreOf($q, $v);
                    @endphp
                    <li class="grid gap-2 px-5 py-3 text-sm sm:grid-cols-[1fr_16rem]">
                        <p>
                            <span class="text-slate-400">{{ $i + 1 }}.</span> {{ $q['text'] }}
                            @if (! empty($q['reverse'])) <span class="text-xs text-amber-700">(pernyataan negatif)</span> @endif
                        </p>
                        <div class="{{ $flag ? 'font-semibold text-red-700' : 'text-slate-800' }}">
                            @switch($q['type'])
                                @case('likert')
                                    {{ $v }} · {{ config('survei.likert_labels')[$v] ?? '' }}
                                    @break
                                @case('multi')
                                    {{ implode(', ', (array) $v) }}
                                    @if (($answers[$q['id'].'_lainnya'] ?? '') !== '')
                                        <span class="block text-slate-600">Lainnya: “{{ $answers[$q['id'].'_lainnya'] }}”</span>
                                    @endif
                                    @break
                                @case('contact')
                                    {{ $v }}@if ($v === 'Ya' && ($answers[$q['id'].'_kontak'] ?? '') !== '') · {{ $answers[$q['id'].'_kontak'] }} @endif
                                    @break
                                @case('text')
                                    @if (trim((string) $v) === '') <span class="text-slate-400">(dikosongkan)</span> @else <span class="whitespace-pre-line">{{ $v }}</span> @endif
                                    @break
                                @default
                                    {{ $v }}
                                    @if (($answers[$q['id'].'_lainnya'] ?? '') !== '')
                                        <span class="block text-slate-600">Lainnya: “{{ $answers[$q['id'].'_lainnya'] }}”</span>
                                    @endif
                            @endswitch
                            @if ($score !== null && ! empty($q['reverse']))
                                <span class="ml-1 text-xs font-normal text-slate-400">(skor {{ $score }})</span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
@endforeach
@endsection
