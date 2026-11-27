@extends('layouts.app')
@section('title', 'Buka laporan')
@section('home', route('laporan.index'))

@section('content')
<x-key-card>
    <h1 class="text-xl font-bold text-slate-900">Laporan hasil survei</h1>
    <p class="mt-2 text-sm text-slate-600">Masukkan kunci pribadi Bapak untuk membuka laporan.</p>
    <form method="POST" action="{{ route('laporan.unlock') }}">
        @csrf
        <x-key-input :autofocus="true" />
        <button class="mt-6 w-full rounded-lg bg-teal-700 px-4 py-2.5 font-semibold text-white hover:bg-teal-800">Buka laporan</button>
    </form>
    <a href="{{ route('laporan.forgot') }}" class="mt-4 block text-center text-sm text-teal-700 hover:underline">Lupa kunci?</a>
</x-key-card>
@endsection
