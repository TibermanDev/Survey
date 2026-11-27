@extends('layouts.app')
@section('title', 'Panitia')

@section('nav')
<form method="POST" action="{{ route('panitia.logout') }}">
    @csrf
    <button class="text-sm text-slate-500 hover:text-slate-800">Keluar</button>
</form>
@endsection

@section('content')
<h1 class="text-2xl font-bold text-slate-900">Panel panitia</h1>
<p class="mt-1 text-slate-600">Buka hari survei sesuai jadwal. Halaman ini hanya menampilkan jumlah pengisi, bukan isi jawaban.</p>

@if ($defaultPassword)
    <div class="mt-6 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        Password panitia masih bawaan. Ganti nilai <code class="font-mono">OPERATOR_PASSWORD</code> di file <code class="font-mono">.env</code> sebelum survei dijalankan.
    </div>
@endif

<div class="mt-6 rounded-2xl border p-4 text-sm {{ $ownerKey ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-300 bg-amber-50 text-amber-900' }}">
    @if ($ownerKey)
        Kunci laporan Pak Andy sudah dibuat pada {{ \Illuminate\Support\Carbon::parse($vaultCreatedAt)->translatedFormat('d F Y, H:i') }}. Hanya Pak Andy yang bisa membaca jawaban.
    @elseif ($temporaryKey)
        <strong>Jawaban masih memakai kunci sementara.</strong> Jawaban tetap terenkripsi, tetapi sampai Pak Andy membuat kuncinya, pengelola server secara teknis masih bisa membukanya.
        Minta Pak Andy segera membuka <span class="font-mono">{{ route('laporan.index') }}</span> dan membuat kuncinya. Semua jawaban akan otomatis dipindah ke kunci beliau.
    @else
        <strong>Kunci laporan Pak Andy belum dibuat.</strong> Survei tetap bisa dibuka; jawaban akan dienkripsi dengan kunci sementara sampai Pak Andy membuat kuncinya di <span class="font-mono">{{ route('laporan.index') }}</span>.
    @endif
</div>

<div class="mt-6 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
    <table class="w-full text-left text-sm">
        <thead class="bg-stone-100 text-slate-600">
            <tr>
                <th class="px-4 py-3 font-medium">Hari</th>
                <th class="px-4 py-3 font-medium">Topik</th>
                <th class="px-4 py-3 text-right font-medium">Pengisi</th>
                <th class="px-4 py-3 font-medium">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-stone-200">
            @foreach ($days as $d)
                <tr>
                    <td class="px-4 py-3 font-semibold">Hari {{ $d->day }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ config("survei.days.{$d->day}.title") }}</td>
                    <td class="px-4 py-3 text-right tabular-nums">{{ $counts[$d->day] ?? 0 }}</td>
                    <td class="px-4 py-3">
                        @if ($d->is_open)
                            <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">Dibuka</span>
                        @else
                            <span class="rounded-full bg-stone-200 px-2.5 py-0.5 text-xs font-semibold text-stone-600">Ditutup</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <form method="POST" action="{{ route('panitia.toggle', $d->day) }}">
                            @csrf
                            <button class="rounded-lg px-3 py-1.5 text-sm font-semibold {{ $d->is_open ? 'border border-stone-300 text-slate-700 hover:bg-stone-50' : 'bg-teal-700 text-white hover:bg-teal-800' }}">
                                {{ $d->is_open ? 'Tutup' : 'Buka' }}
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-6 rounded-2xl border border-stone-200 bg-white p-5 text-sm text-slate-600">
    <p class="font-semibold text-slate-800">Tips</p>
    <ul class="mt-2 list-disc space-y-1 pl-5">
        <li>Tautan untuk karyawan: <span class="font-mono text-slate-800">{{ route('survei.index') }}</span></li>
        <li>Biarkan hari-hari sebelumnya tetap <strong>dibuka</strong> sampai survei selesai, supaya karyawan yang terlewat atau lupa kode masih bisa mengisi.</li>
        <li>Setelah Hari 6 selesai, tutup semua hari.</li>
    </ul>
</div>
@endsection
