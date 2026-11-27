@extends('layouts.app')
@section('title', 'Per karyawan')
@section('home', route('laporan.summary'))
@section('nav') @include('laporan._nav') @endsection

@section('content')
@php
    $filter = request('divisi');
    $people = collect($r['people'])->when($filter, fn ($c) => $c->where('division', $filter));
@endphp

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Hasil per karyawan</h1>
        <p class="mt-1 text-sm text-slate-500">Klik nama untuk melihat jawaban lengkap Hari 1–6.</p>
    </div>
    <form method="GET" class="print:hidden">
        <select name="divisi" onchange="this.form.submit()" class="rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm">
            <option value="">Semua divisi</option>
            @foreach (array_keys($r['divisions']) as $div)
                <option value="{{ $div }}" @selected($filter === $div)>{{ $div }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="mt-4">@include('laporan._legend')</div>

<div class="mt-4 overflow-x-auto rounded-2xl border border-stone-200 bg-white shadow-sm">
    <table class="w-full min-w-[760px] text-left text-sm">
        <thead class="bg-stone-100 text-slate-600">
            <tr>
                <th class="px-4 py-3 font-medium">Karyawan</th>
                <th class="px-4 py-3 font-medium">Divisi</th>
                @foreach ($r['days'] as $d => $s) <th class="px-2 py-3 text-center font-medium">{{ $s['short'] }}</th> @endforeach
                <th class="px-3 py-3 text-center font-semibold text-slate-800">Keseluruhan</th>
                <th class="px-3 py-3 text-center font-medium">Lampu merah</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-stone-200">
            @forelse ($people as $p)
                <tr>
                    <td class="px-4 py-3">
                        <a href="{{ route('laporan.person', $p['code']) }}" class="font-semibold text-teal-700 hover:underline">{{ $p['name'] ?? 'Anonim' }}</a>
                        <div class="font-mono text-xs text-slate-400">{{ $p['code'] }}</div>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $p['division'] }}</td>
                    @foreach (array_keys($r['days']) as $d)
                        <td class="px-2 py-3 text-center"><x-score :value="$p['days'][$d]['score'] ?? null" /></td>
                    @endforeach
                    <td class="px-3 py-3 text-center"><x-score :value="$p['avg']" /></td>
                    <td class="px-3 py-3 text-center">
                        @if (count($p['flags']))
                            <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-bold text-red-800">{{ count($p['flags']) }}</span>
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="px-4 py-8 text-center text-slate-500">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<p class="mt-3 text-xs text-slate-500">Tanda — berarti karyawan tersebut belum mengisi hari itu.</p>
@endsection
