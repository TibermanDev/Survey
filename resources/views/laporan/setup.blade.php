@extends('layouts.app')
@section('title', 'Buat kunci laporan')
@section('home', route('laporan.index'))

@section('content')
<x-key-card>
    <h1 class="text-xl font-bold text-slate-900">Buat kunci laporan</h1>
    <p class="mt-2 text-sm text-slate-600">
        Halaman ini untuk <strong>Pak Andy</strong>. Kunci ini satu-satunya cara membuka hasil survei.
        Seluruh jawaban karyawan akan dikunci dengan kunci ini, sehingga panitia maupun pembuat sistem tidak bisa membacanya.
    </p>
    @if ($pending > 0)
        <p class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
            Sudah ada {{ $pending }} jawaban yang masuk dengan kunci sementara. Semuanya akan otomatis dipindah ke kunci Bapak, lalu kunci sementara dihapus.
        </p>
    @endif
    <form method="POST" action="{{ route('laporan.setup') }}">
        @csrf
        <x-key-input label="Kunci baru" :autofocus="true" hint="Minimal 6 karakter. Gunakan kalimat yang mudah Bapak ingat." />
        <x-key-input name="key_confirmation" label="Ulangi kunci" />
        <button class="mt-6 w-full rounded-lg bg-teal-700 px-4 py-2.5 font-semibold text-white hover:bg-teal-800">Buat kunci</button>
    </form>
</x-key-card>
@endsection
