@extends('layouts.app')
@section('title', 'Kode pemulihan')
@section('home', route('laporan.index'))

@section('content')
<x-key-card>
    <h1 class="text-xl font-bold text-slate-900">{{ $isNew ? 'Kunci berhasil dibuat' : 'Kunci baru tersimpan' }}</h1>
    <p class="mt-2 text-sm text-slate-600">
        Catat <strong>kode pemulihan</strong> di bawah ini dan simpan di tempat aman (misalnya tulis di buku catatan pribadi).
        Kode ini dipakai jika Bapak lupa kunci. Kode ini <strong>hanya ditampilkan sekali</strong>.
    </p>
    <p class="mt-5 rounded-xl border-2 border-dashed border-teal-600 bg-teal-50 px-4 py-5 text-center font-mono text-2xl font-bold tracking-wider text-teal-900 select-all">{{ $recovery }}</p>
    @unless ($isNew)
        <p class="mt-3 text-sm text-amber-800">Kode pemulihan yang lama sudah tidak berlaku.</p>
    @endunless
    <div class="mt-6 flex flex-col gap-3 sm:flex-row print:hidden">
        <button type="button" onclick="window.print()" class="flex-1 rounded-lg border border-stone-300 px-4 py-2.5 font-semibold text-slate-700 hover:bg-stone-50">Cetak</button>
        <a href="{{ route('laporan.summary') }}" class="flex-1 rounded-lg bg-teal-700 px-4 py-2.5 text-center font-semibold text-white hover:bg-teal-800">Sudah saya catat</a>
    </div>
</x-key-card>
@endsection
