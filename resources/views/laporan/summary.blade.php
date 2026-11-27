@extends('layouts.app')
@section('title', 'Ringkasan laporan')
@section('home', route('laporan.summary'))
@section('nav') @include('laporan._nav') @endsection

@section('content')
@php
    use App\Services\SurveyReport;
    $people = $r['people'];
    $flagsByPerson = collect($r['flags'])->groupBy('code');
    $toneStyle = [
        'bad' => ['bg-red-500', 'text-slate-900'],
        'mid' => ['bg-amber-400', 'text-slate-900'],
        'good' => ['bg-emerald-500', 'text-slate-900'],
        'info' => ['bg-slate-400', 'text-slate-700'],
    ];
@endphp

<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Hasil Survei Kesehatan Mental</h1>
        <p class="mt-1 text-sm text-slate-500">Per {{ now()->translatedFormat('d F Y, H:i') }}</p>
    </div>
    @include('laporan._legend')
</div>

@if (empty($people))
    <div class="mt-8 rounded-2xl border border-stone-200 bg-white p-8 text-center text-slate-500">Belum ada jawaban yang masuk.</div>
@else

{{-- 1. Kesimpulan --}}
<section class="mt-6 break-inside-avoid rounded-2xl border border-teal-200 bg-white p-5 shadow-sm sm:p-6">
    <h2 class="text-lg font-bold text-teal-900">Kesimpulan</h2>
    <ul class="mt-3 space-y-2.5">
        @foreach ($r['conclusion'] as $item)
            <li class="flex gap-3 text-[15px] leading-relaxed {{ $toneStyle[$item['tone']][1] }}">
                <span class="mt-2 size-2.5 shrink-0 rounded-full {{ $toneStyle[$item['tone']][0] }}"></span>
                <span>{{ $item['text'] }}</span>
            </li>
        @endforeach
    </ul>
</section>

{{-- 2. Lampu merah --}}
@if ($flagsByPerson->isNotEmpty())
<section class="mt-6 break-inside-avoid">
    <h2 class="text-lg font-bold text-slate-900">Perlu ditindaklanjuti segera</h2>
    <p class="mt-1 text-sm text-slate-500">Karyawan yang memberi jawaban lampu merah.</p>
    <div class="mt-3 grid gap-3 md:grid-cols-2">
        @foreach ($flagsByPerson as $code => $flags)
            @php $f0 = $flags->first(); @endphp
            <div class="rounded-2xl border border-red-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $f0['name'] ?? 'Anonim' }}</p>
                        <p class="text-sm text-slate-500">{{ $f0['division'] }} · <span class="font-mono">{{ $code }}</span></p>
                    </div>
                    <a href="{{ route('laporan.person', $code) }}" class="shrink-0 rounded-lg border border-stone-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-stone-50 print:hidden">Lihat jawaban</a>
                </div>
                <ul class="mt-3 space-y-1.5 text-sm">
                    @foreach ($flags as $f)
                        <li class="flex gap-2">
                            <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-red-500"></span>
                            <span>{{ $f['text'] }} <strong class="text-red-700">{{ $f['answer'] }}</strong></span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</section>
@endif

{{-- 3. Skor per topik --}}
<section class="mt-6 break-inside-avoid">
    <h2 class="text-lg font-bold text-slate-900">Kondisi per topik</h2>
    <div class="mt-3 divide-y divide-stone-200 rounded-2xl border border-stone-200 bg-white shadow-sm">
        @foreach ($r['days'] as $d => $s)
            @php $lvl = SurveyReport::level($s['avg']); @endphp
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3.5 sm:flex-nowrap">
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-slate-900">{{ $s['short'] }}</p>
                    <p class="text-xs text-slate-500">{{ $s['count'] }} orang mengisi</p>
                </div>
                <div class="hidden h-2.5 w-40 overflow-hidden rounded-full bg-stone-100 md:block">
                    @if ($s['avg'] !== null)
                        <div class="h-full rounded-full {{ $lvl['bar'] }}" style="width: {{ ($s['avg'] - 1) / 4 * 100 }}%"></div>
                    @endif
                </div>
                <x-score :value="$s['avg']" :label="true" class="w-44 justify-center" />
            </div>
        @endforeach
    </div>
</section>

{{-- 4. Per divisi --}}
<section class="mt-6 break-inside-avoid">
    <h2 class="text-lg font-bold text-slate-900">Kondisi per divisi</h2>
    <p class="mt-1 text-sm text-slate-500">Angka adalah skor 1–5. Warna menunjukkan kategori.</p>
    <div class="mt-3 overflow-x-auto rounded-2xl border border-stone-200 bg-white shadow-sm">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="bg-stone-100 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Divisi</th>
                    <th class="px-3 py-3 text-right font-medium">Orang</th>
                    @foreach ($r['days'] as $s) <th class="px-2 py-3 text-center font-medium">{{ $s['short'] }}</th> @endforeach
                    <th class="px-3 py-3 text-center font-semibold text-slate-800">Keseluruhan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-200">
                @foreach ($r['divisions'] as $name => $div)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $name }}</td>
                        <td class="px-3 py-3 text-right tabular-nums text-slate-500">{{ $div['count'] }}</td>
                        @foreach ($div['days'] as $v) <td class="px-2 py-3 text-center"><x-score :value="$v" /></td> @endforeach
                        <td class="px-3 py-3 text-center"><x-score :value="$div['avg']" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

{{-- 5. Bersedia dihubungi --}}
@if ($r['contacts'])
<section class="mt-6 break-inside-avoid">
    <h2 class="text-lg font-bold text-slate-900">Bersedia dihubungi langsung</h2>
    <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($r['contacts'] as $p)
            <a href="{{ route('laporan.person', $p['code']) }}" class="rounded-xl border border-stone-200 bg-white px-4 py-3 shadow-sm hover:border-teal-400">
                <p class="font-semibold text-slate-900">{{ $p['name'] ?? 'Anonim' }}</p>
                <p class="text-sm text-slate-500">{{ $p['division'] }}{{ $p['contact'] ? ' · '.$p['contact'] : '' }}</p>
            </a>
        @endforeach
    </div>
</section>
@endif

{{-- 6. Detail (dilipat) --}}
<section class="mt-8 space-y-3 print:hidden">
    <details class="group rounded-2xl border border-stone-200 bg-white shadow-sm">
        <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-4 font-semibold text-slate-900">
            Masukan dan saran dari karyawan
            <span class="text-sm font-normal text-teal-700 group-open:hidden">Tampilkan</span><span class="hidden text-sm font-normal text-teal-700 group-open:inline">Sembunyikan</span>
        </summary>
        <div class="space-y-4 border-t border-stone-200 px-5 py-4">
            @foreach ($r['open'] as $d => $o)
                <div>
                    <p class="text-sm font-medium text-slate-900">{{ $o['question'] }}</p>
                    @if (empty($o['items']))
                        <p class="mt-1 text-sm text-slate-400">Belum ada jawaban.</p>
                    @else
                        <ul class="mt-2 space-y-2">
                            @foreach ($o['items'] as $item)
                                <li class="rounded-lg bg-stone-50 px-3 py-2 text-sm">
                                    <p class="whitespace-pre-line">{{ $item['text'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item['division'] }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    </details>

    <details class="group rounded-2xl border border-stone-200 bg-white shadow-sm">
        <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-4 font-semibold text-slate-900">
            Rincian jawaban pilihan ganda
            <span class="text-sm font-normal text-teal-700 group-open:hidden">Tampilkan</span><span class="hidden text-sm font-normal text-teal-700 group-open:inline">Sembunyikan</span>
        </summary>
        <div class="grid gap-4 border-t border-stone-200 px-5 py-4 md:grid-cols-2">
            @foreach ($r['choices'] as $c)
                @php $total = max(1, array_sum($c['counts'])); @endphp
                <div>
                    <p class="text-sm font-medium">{{ $c['question']['text'] }}</p>
                    <div class="mt-2 space-y-1.5">
                        @foreach ($c['counts'] as $opt => $n)
                            @php $flag = in_array($opt, $c['question']['flag'] ?? [], true); @endphp
                            <div class="text-sm">
                                <div class="flex justify-between gap-2"><span class="{{ $flag ? 'text-red-700' : '' }}">{{ $opt }}</span><span class="tabular-nums text-slate-500">{{ $n }}</span></div>
                                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-stone-100"><div class="h-full rounded-full {{ $flag ? 'bg-red-500' : 'bg-teal-600' }}" style="width: {{ $n / $total * 100 }}%"></div></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </details>
</section>
@endif
@endsection
